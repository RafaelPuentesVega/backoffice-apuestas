<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\Deposit;
use App\Models\Parameter;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DepositController extends Controller
{
    /**
     * Muestra la vista para crear un depósito
     */
    public function create()
    {
        $user = Auth::user();
        
        // Verificar si el usuario ya tiene un depósito pendiente
        $hasPendingDeposit = Deposit::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();
        
        // Obtener montos mínimos y máximos
        $minAmount = Parameter::get('deposit_min_amount', 50);
        $maxAmount = Parameter::get('deposit_max_amount', 10000);
        
        return Inertia::render('deposit/Create', [
            'hasPendingDeposit' => $hasPendingDeposit,
            'depositLimits' => [
                'min' => $minAmount,
                'max' => $maxAmount,
            ],
        ]);
    }

    /**
     * Almacena un nuevo depósito
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 
                function ($attribute, $value, $fail) {
                    $minAmount = Parameter::get('deposit_min_amount', 50);
                    $maxAmount = Parameter::get('deposit_max_amount', 10000);
                    
                    if ($value < $minAmount) {
                        $fail("El monto mínimo de depósito es $" . $minAmount);
                    }
                    
                    if ($value > $maxAmount) {
                        $fail("El monto máximo de depósito es $" . $maxAmount);
                    }
                }
            ],
            'bank_name' => 'required|string|max:255',
            'transaction_reference' => 'required|string|max:255',
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // máximo 2MB
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        
        // Verificar si el usuario ya tiene un depósito pendiente
        $hasPendingDeposit = Deposit::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();
            
        if ($hasPendingDeposit) {
            return back()->withErrors([
                'message' => 'Ya tiene una solicitud de depósito pendiente. Debe esperar a que se procese.'
            ]);
        }
        
        // Subir la imagen del comprobante
        $path = $request->file('receipt_image')->store('deposits/' . $user->id . '/' . date('Y-m-d'), 'public');
        
        // Crear el depósito (siempre a capital)
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'balance_type' => 'capital', // Fijo a capital
            'bank_name' => $request->bank_name,
            'transaction_reference' => $request->transaction_reference,
            'receipt_image' => $path,
            'notes' => $request->notes,
            'status' => 'pendiente',
        ]);
        
        return redirect()->route('deposit.history')
            ->with('success', 'Su solicitud de depósito ha sido enviada correctamente y está pendiente de aprobación.');
    }

    /**
     * Muestra el historial de depósitos del usuario
     */
    public function history()
    {
        $user = Auth::user();
        
        // Obtener los depósitos del usuario
        $deposits = Deposit::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('deposit/History', [
            'deposits' => $deposits
        ]);
    }

    /**
     * Panel de administración para gestionar depósitos
     */
    public function admin()
    {
        // Verificar permisos de administrador
        // $user = Auth::user();
        // if (!$this->isAdmin($user)) {
        //     abort(403, 'No tiene permisos para acceder a esta sección.');
        // }
        
        // Obtener depósitos pendientes
        $pendingDeposits = Deposit::where('status', 'pendiente')
            ->with(['user' => function($query) {
                $query->with('membership'); // Corregir nombre de la relación
            }])
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Obtener depósitos recientes (últimos 30 días)
        $recentDeposits = Deposit::where('status', '!=', 'pendiente')
            ->with(['user' => function($query) {
                $query->with('membership'); // Corregir nombre de la relación
            }])
            ->where('processed_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('processed_at', 'desc')
            ->get();
        
        return Inertia::render('admin/deposit/Index', [
            'pendingDeposits' => $pendingDeposits,
            'recentDeposits' => $recentDeposits
        ]);
    }

    /**
     * Aprobar un depósito
     */
    public function approve(Request $request, Deposit $deposit)
    {
 
        
        // Verificar que el depósito esté pendiente
        if ($deposit->status !== 'pendiente') {
            return redirect()->route('admin.deposits')
                ->with('error', 'Este depósito ya ha sido procesado anteriormente.');
        }
        
        // Procesar en una transacción para asegurar consistencia
        DB::beginTransaction();
        
        try {
            // Actualizar el estado del depósito
            $deposit->status = 'aprobado';
            $deposit->processed_at = now();
            $deposit->save();
            
            // Actualizar el saldo del usuario
            $user = User::findOrFail($deposit->user_id);
            
            // Mapear el tipo de saldo al campo correspondiente en el modelo User
            $balanceField = '';
            if ($deposit->balance_type === 'capital') {
                $balanceField = 'capital_balance';
                $description = 'Depósito aprobado a capital';
            } elseif ($deposit->balance_type === 'earnings') {
                $balanceField = 'earnings_balance';
                $description = 'Depósito aprobado a ganancias';
            } else {
                $balanceField = 'network_balance';
                $description = 'Depósito aprobado a comisiones de red';
            }
            
            // Incrementar el saldo
            $user->$balanceField += $deposit->amount;
            $user->save();
            
            // Registrar la transacción
            BalanceTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'deposit',
                'balance_type' => $deposit->balance_type,
                'amount' => $deposit->amount,
                'status' => 'completed',
                'description' => $description . ' - Ref: ' . $deposit->transaction_reference,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.deposits')
                ->with('success', 'Depósito aprobado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.deposits')
                ->with('error', 'Error al procesar el depósito: ' . $e->getMessage());
        }
    }

    /**
     * Rechazar un depósito
     */
    public function reject(Request $request, Deposit $deposit)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);
        
 
        
        // Verificar que el depósito esté pendiente
        if ($deposit->status !== 'pendiente') {
            return redirect()->route('admin.deposits')
                ->with('error', 'Este depósito ya ha sido procesado anteriormente.');
        }
        
        // Actualizar el estado del depósito
        $deposit->status = 'rechazado';
        $deposit->processed_at = now();
        $deposit->notes = $deposit->notes . "\n\nRazón de rechazo: " . $request->rejection_reason;
        $deposit->save();
        
        return redirect()->route('admin.deposits')
            ->with('success', 'Depósito rechazado correctamente.');
    }

    /**
     * Muestra el comprobante de depósito
     */
    public function showReceipt(Deposit $deposit)
    {
        // Verificar que exista el archivo
        if (!Storage::disk('public')->exists($deposit->receipt_image)) {
            abort(404, 'El comprobante no se encuentra disponible.');
        }
        
        // Obtener el contenido del archivo
        return response()->file(Storage::disk('public')->path($deposit->receipt_image));
    }
    
    /**
     * Verifica si un usuario es administrador
     */
    private function isAdmin(User $user): bool
    {
        // Verificar por ID de rol (esto depende de cómo esté configurada la base de datos)
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