<?php

namespace App\Http\Controllers;

use App\Models\BalanceTransaction;
use App\Models\Comision;
use App\Models\ProfitDistribution;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    /**
     * Muestra el dashboard de transacciones con filtros
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : Carbon::now()->endOfDay();
        $type = $request->input('type', 'all');
        
        // Obtener todas las transacciones del usuario según el filtro
        $query = BalanceTransaction::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc');
        
        if ($type !== 'all') {
            $query->where('balance_type', $type);
        }
        
        $transactions = $query->get();
        
        // Calcular totales para el periodo seleccionado
        $totals = [
            'capital' => $transactions->where('balance_type', 'capital')->sum('amount'),
            'earnings' => $transactions->where('balance_type', 'earnings')->sum('amount'),
            'network' => $transactions->where('balance_type', 'network')->sum('amount'),
            'all' => $transactions->sum('amount'),
        ];
        
        // Obtener distribución de ganancias
        $profitDistributions = ProfitDistribution::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'totals' => $totals,
            'profitDistributions' => $profitDistributions,
            'filters' => [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'type' => $type,
            ],
        ]);
    }
    
    /**
     * Muestra el reporte de comisiones de red
     */
    public function networkCommissions(Request $request)
    {
        $user = Auth::user();
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : Carbon::now()->endOfDay();
        
        // Obtener referidos directos
        $referrals = User::where('sponsor_id', $user->id)->get();
        $referralIds = $referrals->pluck('id')->toArray();
        
        // Obtener comisiones recibidas
        $commissions = Comision::where('sponsor_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'desc')
            ->with('user:id,name,email')
            ->with('membresia:id,nombre')
            ->get();
        
        // Agrupar comisiones por referido
        $commissionsByReferral = $commissions->groupBy('user_id');
        
        // Calcular estadísticas
        $stats = [
            'total_commissions' => $commissions->sum('monto'),
            'total_referrals' => count($referralIds),
            'active_referrals' => count($commissionsByReferral),
            'commissions_by_date' => $commissions->groupBy(function($item) {
                return Carbon::parse($item->created_at)->format('Y-m-d');
            })->map(function($group) {
                return $group->sum('monto');
            }),
        ];
        
        return Inertia::render('Transactions/NetworkCommissions', [
            'commissions' => $commissions,
            'stats' => $stats,
            'referrals' => $referrals,
            'filters' => [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }
    
    /**
     * Genera un reporte consolidado para el período seleccionado
     */
    public function consolidatedReport(Request $request)
    {
        $user = Auth::user();
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : Carbon::now()->endOfDay();
        
        // Obtener transacciones agrupadas por día y tipo
        $dailyTransactions = BalanceTransaction::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(created_at) as date'),
                'balance_type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date', 'balance_type')
            ->orderBy('date')
            ->get();
        
        // Organizar datos para gráfico
        $dates = $dailyTransactions->pluck('date')->unique()->values();
        
        $chartData = [
            'labels' => $dates->toArray(),
            'datasets' => [
                [
                    'label' => 'Capital',
                    'data' => $this->prepareChartData($dailyTransactions, $dates, 'capital'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgb(75, 192, 192)',
                ],
                [
                    'label' => 'Ganancias',
                    'data' => $this->prepareChartData($dailyTransactions, $dates, 'earnings'),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                ],
                [
                    'label' => 'Red',
                    'data' => $this->prepareChartData($dailyTransactions, $dates, 'network'),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                    'borderColor' => 'rgb(153, 102, 255)',
                ],
            ],
        ];
        
        // Obtener totales
        $totals = [
            'capital' => $dailyTransactions->where('balance_type', 'capital')->sum('total'),
            'earnings' => $dailyTransactions->where('balance_type', 'earnings')->sum('total'),
            'network' => $dailyTransactions->where('balance_type', 'network')->sum('total'),
            'all' => $dailyTransactions->sum('total'),
        ];
        
        return Inertia::render('Transactions/ConsolidatedReport', [
            'chartData' => $chartData,
            'totals' => $totals,
            'filters' => [
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
        ]);
    }
    
    /**
     * Prepara los datos para el gráfico
     */
    private function prepareChartData($transactions, $dates, $type)
    {
        $data = [];
        
        foreach ($dates as $date) {
            $value = $transactions
                ->where('date', $date)
                ->where('balance_type', $type)
                ->first();
                
            $data[] = $value ? $value->total : 0;
        }
        
        return $data;
    }
} 