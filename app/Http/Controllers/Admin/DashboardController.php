<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\BalanceTransaction;
use App\Models\Membresia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Muestra el panel de administración
     */
    public function index(Request $request)
    {
        // Ya no necesitamos esta validación porque lo hacemos con middleware
        // if (!$this->isAdmin($request->user())) {
        //     return redirect()->route('dashboard');
        // }
        
        // Estadísticas generales
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('membership_id')->count();
        $inactiveUsers = $totalUsers - $activeUsers;
        
        // Fecha de inicio del mes actual
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // Estadísticas de la plataforma
        $platformStats = [
            'totalProfit' => 0, // Valor real debe venir de una consulta
            'profitThisMonth' => 0, // Valor real debe venir de una consulta
            'profitGrowth' => 0, // Porcentaje de crecimiento
            'userEarnings' => 0, // Valor real debe venir de una consulta
            'userEarningsGrowth' => 0, // Porcentaje de crecimiento
            'commissionPaid' => 0, // Valor real debe venir de una consulta
            'feeCollected' => 0, // Valor real debe venir de una consulta
        ];
        
        // Estadísticas de depósitos
        $depositsStats = [
            'pending' => Deposit::where('status', 'pendiente')->count(),
            'approved' => Deposit::where('status', 'aprobado')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'rejected' => Deposit::where('status', 'rechazado')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'totalAmount' => Deposit::where('status', 'aprobado')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
        ];
        
        // Estadísticas de retiros
        $withdrawalsStats = [
            'pending' => Withdrawal::where('status', 'pendiente')->count(),
            'approved' => Withdrawal::where('status', 'completado')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'rejected' => Withdrawal::where('status', 'rechazado')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'totalAmount' => Withdrawal::where('status', 'completado')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount'),
        ];
        
        // Obtener membresías pendientes de aprobación
        $pendingMemberships = User::whereNotNull('pending_membership_id')
            ->with(['pendingMembership', 'deposits' => function ($query) {
                $query->where('status', 'pendiente')
                      ->where('notes', 'like', 'Pago de membresía:%')
                      ->latest();
            }])
            ->get()
            ->map(function ($user) {
                $deposit = $user->deposits->first();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'pendingMembership' => $user->pendingMembership ? [
                        'id' => $user->pendingMembership->id,
                        'nombre' => $user->pendingMembership->nombre,
                        'precio' => $user->pendingMembership->precio,
                    ] : null,
                    'deposit' => $deposit ? [
                        'id' => $deposit->id,
                        'amount' => $deposit->amount,
                        'created_at' => $deposit->created_at,
                        'payment_proof' => $deposit->payment_proof,
                    ] : null,
                ];
            });
        
        // Transacciones recientes
        $latestTransactions = BalanceTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Crecimiento de usuarios por día (últimos 30 días)
        $userGrowth = $this->getUserGrowthStats();
        
        // Top 5 usuarios con mayor balance
        $topUsersByBalance = User::select(
                'id', 
                'name', 
                'email',
                DB::raw('(capital_balance + earnings_balance + network_balance) as total_balance')
            )
            ->orderByDesc('total_balance')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'totalBalance' => $user->total_balance,
                ];
            });
        
        // Top 5 usuarios por ganancias
        $topUsersByEarnings = User::select(
                'id', 
                'name', 
                'email',
                DB::raw('earnings_balance as total_balance')
            )
            ->orderByDesc('earnings_balance')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'totalBalance' => $user->total_balance,
                ];
            });
        
        // Distribución por membresías
        $membershipDistribution = $this->getMembershipDistribution();
        
        // Datos de tendencia de ganancias (últimos 6 meses)
        $profitTrend = $this->getProfitTrendData();
        
        return Inertia::render('AdminDashboard', [
            'isAdmin' => true,
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'inactiveUsers' => $inactiveUsers,
                'depositsStats' => $depositsStats,
                'withdrawalsStats' => $withdrawalsStats,
                'platformStats' => $platformStats,
            ],
            'latestTransactions' => $latestTransactions,
            'userGrowth' => $userGrowth,
            'topUsersByBalance' => $topUsersByBalance,
            'topUsersByEarnings' => $topUsersByEarnings,
            'membershipDistribution' => $membershipDistribution,
            'profitTrend' => $profitTrend,
            'pendingMemberships' => $pendingMemberships,
        ]);
    }
    
    /**
     * Obtiene estadísticas de crecimiento de usuarios
     */
    private function getUserGrowthStats()
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $stats = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayKey = $currentDate->format('Y-m-d');
            $dayLabel = $currentDate->format('d M');
            
            $newUsers = User::whereDate('created_at', $dayKey)->count();
            
            $stats[] = [
                'date' => $dayLabel,
                'newUsers' => $newUsers,
            ];
            
            $currentDate->addDay();
        }
        
        return $stats;
    }
    
    /**
     * Obtiene distribución de usuarios por membresía
     */
    private function getMembershipDistribution()
    {
        $memberships = Membresia::all();
        $distribution = [];
        
        foreach ($memberships as $membership) {
            $userCount = User::where('membership_id', $membership->id)->count();
            
            $distribution[] = [
                'name' => $membership->nombre,
                'count' => $userCount,
            ];
        }
        
        // Agregar usuarios sin membresía
        $noMembershipCount = User::whereNull('membership_id')->count();
        if ($noMembershipCount > 0) {
            $distribution[] = [
                'name' => 'Sin membresía',
                'count' => $noMembershipCount,
            ];
        }
        
        return $distribution;
    }
    
    /**
     * Obtiene datos de tendencia de ganancias (últimos 6 meses)
     */
    private function getProfitTrendData()
    {
        $data = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(5)->startOfMonth(); // 6 meses incluyendo el actual
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lessThanOrEqualTo($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();
            $monthLabel = $currentDate->format('M Y');
            
            // Estos valores deben ser calculados desde la base de datos en una implementación real
            // Aquí usamos datos de ejemplo
            $platformProfit = rand(1000, 5000); // Ganancias de la plataforma
            $userEarnings = rand(2000, 8000);  // Ganancias de los usuarios
            
            $data[] = [
                'month' => $monthLabel,
                'profit' => $platformProfit,
                'userEarnings' => $userEarnings,
            ];
            
            $currentDate->addMonth();
        }
        
        return $data;
    }
    
    /**
     * Verifica si un usuario es administrador
     */
    private function isAdmin($user): bool
    {
        if (!$user) return false;
        
        return $user->hasRole(['admin', 'super-admin']);
    }
} 