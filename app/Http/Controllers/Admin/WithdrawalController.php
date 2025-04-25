<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\BalanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    /**
     * Muestra la lista de retiros para administración
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search', '');
        
        $query = Withdrawal::with('user')
            ->orderBy('created_at', 'desc');
        
        // Filtrar por estado si se especifica
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Buscar por email o nombre de usuario
        if (!empty($search)) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $withdrawals = $query->paginate(10)
            ->withQueryString();
        
        // Estadísticas rápidas
        $stats = [
            'pendientes' => Withdrawal::where('status', 'pendiente')->count(),
            'completados' => Withdrawal::where('status', 'completado')->count(),
            'rechazados' => Withdrawal::where('status', 'rechazado')->count(),
            'total' => Withdrawal::count(),
        ];
        
        return Inertia::render('admin/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }
    
    /**
     * Muestra los detalles de un retiro específico
     */
    public function show(Withdrawal $withdrawal)
    {
        $withdrawal->load('user.wallet');
        
        return Inertia::render('admin/Withdrawals/Show', [
            'withdrawal' => $withdrawal,
        ]);
    }
    
    /**
     * Aprobar un retiro pendiente
     */
    public function approve(Request $request, Withdrawal $withdrawal)
    {
        // Verificar que el retiro esté pendiente
        if ($withdrawal->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden aprobar retiros pendientes');
        }
        
        try {
            DB::beginTransaction();
            
            // Actualizar el estado del retiro
            $withdrawal->status = 'completado';
            $withdrawal->processed_at = Carbon::now();
            $withdrawal->save();
            
            // Determinar el tipo de balance basado en otros datos disponibles
            // Si no existe balance_type, usar 'capital' como valor por defecto
            $balanceType = 'capital'; // Valor por defecto
            
            // Registrar la transacción
            BalanceTransaction::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal',
                'balance_type' => $balanceType,
                'amount' => -$withdrawal->amount, // Valor negativo para retiros
                'status' => 'completado',
                'description' => 'Retiro aprobado: ' . $withdrawal->wallet_address,
                'balance_after' => $this->getCurrentBalance($withdrawal->user_id, $balanceType),
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Retiro aprobado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al aprobar el retiro: ' . $e->getMessage());
        }
    }
    
    /**
     * Rechazar un retiro pendiente
     */
    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        
        // Verificar que el retiro esté pendiente
        if ($withdrawal->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden rechazar retiros pendientes');
        }
        
        try {
            DB::beginTransaction();
            
            // Actualizar el estado del retiro
            $withdrawal->status = 'rechazado';
            $withdrawal->processed_at = Carbon::now();
            $withdrawal->description = $request->input('reason');
            $withdrawal->save();
            
            // Determinar el tipo de balance basado en otros datos disponibles
            // Si no existe balance_type, usar 'capital' como valor por defecto
            $balanceType = 'capital'; // Valor por defecto
            
            // Devolver el saldo al usuario
            $user = User::find($withdrawal->user_id);
            
            // Actualizar el balance correcto según el tipo
            $user->capital_balance += $withdrawal->amount;
            $user->save();
            
            // Registrar la transacción de devolución
            BalanceTransaction::create([
                'user_id' => $withdrawal->user_id,
                'transaction_type' => 'withdrawal_refund',
                'balance_type' => $balanceType,
                'amount' => $withdrawal->amount, // Valor positivo para devoluciones
                'status' => 'completado',
                'description' => 'Retiro rechazado: ' . $request->input('reason'),
                'balance_after' => $this->getCurrentBalance($withdrawal->user_id, $balanceType),
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Retiro rechazado y saldo devuelto al usuario');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al rechazar el retiro: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener el balance actual de un usuario
     */
    private function getCurrentBalance($userId, $balanceType)
    {
        $user = User::find($userId);
        // Como estamos usando solo el balance de capital, simplificamos este método
        return $user->capital_balance;
    }
} 