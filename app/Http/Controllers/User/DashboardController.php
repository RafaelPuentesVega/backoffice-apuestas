<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\BalanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard interactivo para el usuario
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener la membresía del usuario
        $membership = null;
        if ($user->membership_id) {
            $membership = \App\Models\Membresia::find($user->membership_id);
        }
        
        // Formatear datos de membresía en el formato esperado por el componente
        $formattedMembership = $membership ? [
            'id' => $membership->id,
            'nombre' => $membership->nombre,
            'precio' => $membership->precio,
            'comision_directa' => $membership->comision_directa,
            'porcentaje_rendimiento' => $membership->porcentaje_rendimiento,
        ] : null;
        
        // Verificar si existe una membresía pendiente
        $pendingMembership = null;
        $formattedPendingMembership = null;
        
        if ($user->pending_membership_id) {
            $pendingMembership = \App\Models\Membresia::find($user->pending_membership_id);
            
            if ($pendingMembership) {
                $formattedPendingMembership = [
                    'id' => $pendingMembership->id,
                    'nombre' => $pendingMembership->nombre,
                    'precio' => $pendingMembership->precio,
                    'comision_directa' => $pendingMembership->comision_directa,
                    'porcentaje_rendimiento' => $pendingMembership->porcentaje_rendimiento,
                ];
            }
        }
        
        // Obtener límites de depósito y retiro de configuración
        $depositLimit = [
            'min' => (int)config('deposit.min_amount', 10000),
            'max' => (int)config('deposit.max_amount', 1000000)
        ];
        
        $withdrawalLimit = [
            'min' => (int)config('withdrawal.min_amount', 20000),
            'max' => (int)config('withdrawal.max_amount', 500000)
        ];
        
        // Últimas transacciones
        $recentTransactions = BalanceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                // Mapear tipos específicos de transacciones
                $type = $transaction->transaction_type;
                
                // Si es una transacción de membresía, asegurarnos de que se muestre como tal
                if (strpos($transaction->description, 'membresía') !== false || 
                    strpos($transaction->description, 'membresia') !== false) {
                    $type = 'membership_payment';
                }
                
                return [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'type' => $type,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->toDateTimeString(),
                    'description' => $transaction->description,
                    'reference' => $transaction->reference ?? $transaction->id,
                ];
            });
        
        // Historial de balance (últimas 2 semanas)
        $balanceHistory = $this->getDetailedBalanceHistory($user->id);
        
        // Referidos recientes
        $referrals = User::where('referrer_id', $user->id)
            ->select('id', 'name', 'email', 'created_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'name' => $referral->name,
                    'email' => $referral->email,
                    'date' => $referral->created_at->toDateTimeString(),
                    'status' => $this->getReferralStatus($referral),
                ];
            });
        
        // Acciones pendientes
        $pendingActions = [
            'deposits' => Deposit::where('user_id', $user->id)
                ->where('status', 'pendiente')
                ->count(),
            'withdrawals' => Withdrawal::where('user_id', $user->id)
                ->where('status', 'pendiente')
                ->count()
        ];
        
        // Obtener código de referido
        $referralCode = $user->code_referral;
        
        // Preparar datos de usuario para la vista en el formato esperado
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'capital_balance' => $user->capital_balance,
            'earnings_balance' => $user->earnings_balance,
            'network_balance' => $user->network_balance,
            'membership' => $formattedMembership,
            'membership_expires_at' => $user->membership_expires_at,
            'code_referral' => $referralCode,
            'pending_membership' => $formattedPendingMembership,
        ];
        
        return Inertia::render('UserDashboard', [
            'user' => $userData,
            'balanceHistory' => $balanceHistory,
            'recentTransactions' => $recentTransactions,
            'referrals' => $referrals,
            'pendingActions' => $pendingActions,
            'depositLimit' => $depositLimit,
            'withdrawalLimit' => $withdrawalLimit,
        ]);
    }
    
    /**
     * Obtener historial detallado de balance para los gráficos
     */
    private function getDetailedBalanceHistory($userId)
    {
        $startDate = Carbon::now()->subDays(14)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $history = [];
        $currentDate = $startDate->copy();
        
        // Inicializar valores
        $capitalBalance = User::find($userId)->capital_balance ?? 0;
        $earningsBalance = User::find($userId)->earnings_balance ?? 0;
        $networkBalance = User::find($userId)->network_balance ?? 0;
        
        while ($currentDate <= $endDate) {
            $dayKey = $currentDate->format('Y-m-d');
            
            // Simular variaciones para demo o usar datos reales si existen
            // En un sistema real, esto debería consultar balances históricos reales
            $history[] = [
                'date' => $dayKey,
                'capital' => $capitalBalance,
                'earnings' => $earningsBalance,
                'network' => $networkBalance
            ];
            
            // Simulación de cambios diarios para el demo 
            // (reemplazar con datos reales en producción)
            $capitalBalance += rand(-5, 15) / 100 * $capitalBalance;
            $earningsBalance += rand(1, 8) / 100 * $earningsBalance;
            $networkBalance += rand(0, 5) / 100 * $networkBalance;
            
            $currentDate->addDay();
        }
        
        return $history;
    }
    
    /**
     * Determinar el estado de un referido
     */
    private function getReferralStatus($user)
    {
        if ($user->membership_id) {
            return 'approved';
        } else {
            return 'pending';
        }
    }
} 