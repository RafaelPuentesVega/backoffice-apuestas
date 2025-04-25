<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membresia;
use App\Models\MembershipPayment;
use App\Models\User;
use App\Models\MembershipHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class MembershipStatsController extends Controller
{
    /**
     * Muestra las estadísticas de pagos de membresías
     */
    public function index(Request $request)
    {
        // Configurar rango de fechas
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfDay();
        
        // Asegurarse que las fechas estén en el formato correcto
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();
        
        // Estadísticas generales de membresías
        $totalPayments = MembershipPayment::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalAmount = MembershipPayment::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $pendingPayments = MembershipPayment::where('status', 'pendiente')->count();
        $approvedPayments = MembershipPayment::where('status', 'aprobado')->whereBetween('processed_at', [$startDate, $endDate])->count();
        $rejectedPayments = MembershipPayment::where('status', 'rechazado')->whereBetween('processed_at', [$startDate, $endDate])->count();
        
        // Obtener estadísticas por tipo de membresía
        $membershipStats = DB::table('membership_payments')
            ->join('membresias', 'membership_payments.membresia_id', '=', 'membresias.id')
            ->whereBetween('membership_payments.created_at', [$startDate, $endDate])
            ->select(
                'membresias.id', 
                'membresias.nombre', 
                'membresias.precio',
                DB::raw('COUNT(*) as total_payments'),
                DB::raw('SUM(membership_payments.amount) as total_amount'),
                DB::raw('COUNT(CASE WHEN membership_payments.status = "aprobado" THEN 1 END) as approved_count'),
                DB::raw('COUNT(CASE WHEN membership_payments.status = "pendiente" THEN 1 END) as pending_count'),
                DB::raw('COUNT(CASE WHEN membership_payments.status = "rechazado" THEN 1 END) as rejected_count')
            )
            ->groupBy('membresias.id', 'membresias.nombre', 'membresias.precio')
            ->orderBy('total_payments', 'desc')
            ->get();
        
        // Tendencia de pagos por día
        $dailyTrend = DB::table('membership_payments')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(amount) as amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d M'),
                    'total' => $item->total,
                    'amount' => (float) $item->amount,
                ];
            });
        
        // Usuarios más recientes que han pagado membresía
        $recentPayments = MembershipPayment::with(['user', 'membresia'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user' => [
                        'id' => $payment->user->id,
                        'name' => $payment->user->name,
                        'email' => $payment->user->email,
                    ],
                    'membresia' => [
                        'id' => $payment->membresia->id,
                        'nombre' => $payment->membresia->nombre,
                        'precio' => $payment->membresia->precio,
                    ],
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'processed_at' => $payment->processed_at,
                ];
            });
        
        // Preparar datos para gráficos
        $chartData = [
            'membershipDistribution' => [
                'labels' => $membershipStats->pluck('nombre'),
                'data' => $membershipStats->pluck('total_payments'),
            ],
            'dailyTrend' => [
                'labels' => $dailyTrend->pluck('date'),
                'payments' => $dailyTrend->pluck('total'),
                'amounts' => $dailyTrend->pluck('amount'),
            ]
        ];
        
        return Inertia::render('admin/membership/Statistics', [
            'filters' => [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ],
            'stats' => [
                'totalPayments' => $totalPayments,
                'totalAmount' => $totalAmount,
                'pendingPayments' => $pendingPayments,
                'approvedPayments' => $approvedPayments,
                'rejectedPayments' => $rejectedPayments,
            ],
            'membershipStats' => $membershipStats,
            'recentPayments' => $recentPayments,
            'chartData' => $chartData,
        ]);
    }
    
    /**
     * Muestra una lista detallada de todos los pagos de membresías
     */
    public function payments(Request $request)
    {
        // Configuración de filtros
        $filters = $request->only(['search', 'status', 'membership_id', 'date_from', 'date_to']);
        
        // Construir la consulta base
        $query = MembershipPayment::with(['user', 'membresia'])
            ->orderBy('created_at', 'desc');
        
        // Aplicar filtros
        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['membership_id'])) {
            $query->where('membresia_id', $filters['membership_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Obtener las membresías para el filtro
        $membresias = Membresia::orderBy('nombre')->get();
        
        // Paginar los resultados
        $payments = $query->paginate(15)->withQueryString();
        
        return Inertia::render('admin/membership/Payments', [
            'filters' => $filters,
            'membresias' => $membresias,
            'payments' => $payments,
        ]);
    }
} 