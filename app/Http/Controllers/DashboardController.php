<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\BalanceTransaction;
use App\Models\Membresia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard correspondiente según el rol del usuario
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Verificar si el usuario es administrador
        if ($this->isAdmin($user)) {
            return redirect()->route('admin.dashboard');
        }
        
        // Dashboard para usuario normal
        return $this->userDashboard($request);
    }
    
    /**
     * Dashboard para usuarios normales
     */
    private function userDashboard(Request $request)
    {
        $user = Auth::user();
        
        // Obtener la membresía del usuario
        $membership = null;
        if ($user->membership_id) {
            $membership = \App\Models\Membresia::find($user->membership_id);
            $user->membership = $membership;
        }
        
        // Obtener la membresía pendiente si existe
        $pendingMembership = null;
        if ($user->pending_membership_id) {
            $pendingMembership = \App\Models\Membresia::find($user->pending_membership_id);
            $user->pendingMembership = $pendingMembership;
        }
        
        // Obtener estadísticas de balances
        $balances = [
            'capital' => $user->capital_balance,
            'earnings' => $user->earnings_balance,
            'network' => $user->network_balance,
            'total' => $user->capital_balance + $user->earnings_balance + $user->network_balance
        ];
        
        // Últimas transacciones (5)
        $latestTransactions = BalanceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Estado de depósitos recientes
        $recentDeposits = Deposit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
        
        // Estado de retiros recientes
        $recentWithdrawals = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
        
        // Datos para gráficos - últimos 7 días de transacciones
        $lastWeekStats = $this->getLastWeekTransactionsStats($user->id);
        
        // Información de red (referidos)
        $referralsCount = User::where('referrer_id', $user->id)->count();
        $activeReferralsCount = User::where('referrer_id', $user->id)
            ->whereNotNull('membership_id')
            ->count();
        
        // Próximos pagos o eventos
        $upcomingEvents = $this->getUpcomingEvents($user);
        
        // Verificar pago de membresía pendiente
        $pendingMembershipPayment = Deposit::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->where('notes', 'like', 'Pago de membresía:%')
            ->latest()
            ->first();
        
        return Inertia::render('Dashboard', [
            'isAdmin' => false,
            'user' => $user,
            'balances' => $balances,
            'latestTransactions' => $latestTransactions,
            'recentDeposits' => $recentDeposits,
            'recentWithdrawals' => $recentWithdrawals,
            'lastWeekStats' => $lastWeekStats,
            'referralsStats' => [
                'total' => $referralsCount,
                'active' => $activeReferralsCount,
            ],
            'upcomingEvents' => $upcomingEvents,
            'pendingMembership' => $pendingMembership,
            'pendingMembershipPayment' => $pendingMembershipPayment
        ]);
    }
    
    /**
     * Dashboard para administradores
     */
    private function adminDashboard(Request $request)
    {
        // Estadísticas generales
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('membership_id')->count();
        
        // Obtener las últimas transacciones para mostrar en el dashboard
        $latestTransactions = BalanceTransaction::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'user' => [
                        'id' => $tx->user->id,
                        'name' => $tx->user->name,
                        'email' => $tx->user->email,
                    ],
                    'transaction_type' => $tx->transaction_type,
                    'balance_type' => $tx->balance_type,
                    'amount' => $tx->amount,
                    'status' => $tx->status,
                    'description' => $tx->description,
                    'created_at' => $tx->created_at->format('Y-m-d H:i:s'),
                ];
            });
        
        // Total de depósitos y retiros en el último mes
        $lastMonth = Carbon::now()->subMonth();
        
        $depositsStats = [
            'pending' => Deposit::where('status', 'pendiente')->count(),
            'approved' => Deposit::where('status', 'aprobado')
                ->where('created_at', '>=', $lastMonth)
                ->count(),
            'rejected' => Deposit::where('status', 'rechazado')
                ->where('created_at', '>=', $lastMonth)
                ->count(),
            'totalAmount' => Deposit::where('status', 'aprobado')
                ->where('created_at', '>=', $lastMonth)
                ->sum('amount'),
        ];
        
        $withdrawalsStats = [
            'pending' => Withdrawal::where('status', 'pendiente')->count(),
            'approved' => Withdrawal::where('status', 'aprobado')
                ->where('created_at', '>=', $lastMonth)
                ->count(),
            'rejected' => Withdrawal::where('status', 'rechazado')
                ->where('created_at', '>=', $lastMonth)
                ->count(),
            'totalAmount' => Withdrawal::where('status', 'aprobado')
                ->where('created_at', '>=', $lastMonth)
                ->sum('amount'),
        ];
        
        // Crecimiento de usuarios por día (últimos 30 días)
        $userGrowth = $this->getUserGrowthStats();
        
        // Top 5 usuarios con mayor balance
        $topUsersByBalance = User::orderByRaw('(capital_balance + earnings_balance + network_balance) DESC')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'totalBalance' => $user->capital_balance + $user->earnings_balance + $user->network_balance,
                ];
            });
        
        // Distribución por membresías
        $membershipDistribution = $this->getMembershipDistribution();
        
        return Inertia::render('AdminDashboard', [
            'isAdmin' => true,
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'depositsStats' => $depositsStats,
                'withdrawalsStats' => $withdrawalsStats,
            ],
            'latestTransactions' => $latestTransactions,
            'userGrowth' => $userGrowth,
            'topUsersByBalance' => $topUsersByBalance,
            'membershipDistribution' => $membershipDistribution,
        ]);
    }
    
    /**
     * Obtiene estadísticas de transacciones de la última semana
     */
    private function getLastWeekTransactionsStats($userId)
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $stats = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayKey = $currentDate->format('Y-m-d');
            $dayLabel = $currentDate->format('d M');
            
            $deposits = Deposit::where('user_id', $userId)
                ->where('status', 'aprobado')
                ->whereDate('processed_at', $dayKey)
                ->sum('amount');
                
            $withdrawals = Withdrawal::where('user_id', $userId)
                ->where('status', 'aprobado')
                ->whereDate('processed_at', $dayKey)
                ->sum('amount');
            
            $stats[] = [
                'date' => $dayLabel,
                'deposits' => (float) $deposits,
                'withdrawals' => (float) $withdrawals,
            ];
            
            $currentDate->addDay();
        }
        
        return $stats;
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
     * Obtiene próximos eventos para el usuario
     */
    private function getUpcomingEvents($user)
    {
        $events = [];
        
        // Aquí se podrían agregar lógicas específicas para eventos como:
        // - Próximos pagos
        // - Fechas de renovación de membresía
        // - Anuncios o promociones
        
        return $events;
    }
    
    /**
     * Verifica si un usuario es administrador
     */
    private function isAdmin(User $user): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->whereIn('role_id', function($query) {
                $query->select('id')
                    ->from('roles')
                    ->whereIn('name', ['admin', 'super-admin']);
            })
            ->exists();
    }
} 