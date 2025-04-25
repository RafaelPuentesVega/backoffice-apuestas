<?php

namespace App\Http\Controllers;

use App\Models\Membresia;
use App\Models\User;
use App\Models\Wallet;
use App\Models\MembershipHistory;
use App\Models\MembershipPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\Deposit;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use App\Mail\MembershipPaymentReceived;

class MembershipController extends Controller
{
    /**
     * Muestra la página de selección de membresía.
     */
    public function select()
    {
        // Obtener todas las membresías disponibles
        $membresias = Membresia::all();
        
        // Obtener la membresía activa del usuario actual si existe
        $user = Auth::user();
        
        // Verificar si el usuario ya tiene una membresía pendiente
        if ($user->pending_membership_id) {
            $pendingMembership = Membresia::find($user->pending_membership_id);
            return redirect()->route('dashboard')->with('info', 'Ya tienes una solicitud de membresía pendiente de aprobación: ' . ($pendingMembership->nombre ?? 'Membresía') . '. Por favor, espera a que sea aprobada o contacta a soporte.');
        }
        
        $activeMembership = null;
        
        if ($user->membership_id) {
            $activeMembership = Membresia::find($user->membership_id);
        }
        
        return Inertia::render('Membership/Select', [
            'membresias' => $membresias,
            'user' => $user,
            'activeMembership' => $activeMembership
        ]);
    }
    
    /**
     * Activa la membresía seleccionada para el usuario.
     * (Solo para membresías sin costo)
     */
    public function activate(Request $request)
    {
        $request->validate([
            'membresia_id' => 'required|exists:membresias,id'
        ]);
        
        $user = Auth::user();
        $membresia = Membresia::findOrFail($request->membresia_id);
        
        // Verificar que la membresía sea gratuita
        if ($membresia->precio > 0) {
            // Registrar intento de activar membresía no gratuita
            Log::warning('Intento de activar membresía de pago como gratuita', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'precio' => $membresia->precio
            ]);
            
            return redirect()->back()->with('error', 'Esta membresía tiene un costo. Debe realizar el pago correspondiente.');
        }
        
        // Verificar las reglas de negocio para cambiar de membresía
        $canChangeMembership = true;
        $errorMessage = null;
        
        // Si el usuario ya tiene una membresía activa
        if ($user->membership_id) {
            $activeMembership = Membresia::find($user->membership_id);
            $remainingDays = $this->getRemainingDays($user);
            
            // Verificamos primero si la membresía ha expirado - en ese caso, permitimos cambiar a cualquier membresía
            $isExpired = $user->membership_expires_at && Carbon::parse($user->membership_expires_at)->isPast();
            
            if ($isExpired) {
                // Si la membresía está expirada, permitir cambio sin restricciones
                $canChangeMembership = true;
                Log::info('Permitiendo cambio de membresía porque la actual está expirada', [
                    'user_id' => $user->id,
                    'current_membership_id' => $user->membership_id,
                    'new_membership_id' => $membresia->id,
                    'expired_at' => $user->membership_expires_at
                ]);
            }
            // Si la membresía actual tiene costo y no está expirada, aplicar restricciones
            else if ($activeMembership && $activeMembership->precio > 0) {
                // Si quedan más de 15 días, no puede cambiar a ninguna
                if ($remainingDays > 15) {
                    $canChangeMembership = false;
                    $errorMessage = 'No puedes cambiar de membresía cuando te quedan más de 15 días en tu membresía actual.';
                }
                // Si no es la misma membresía y quedan entre 10-15 días, tampoco puede cambiar
                elseif ($remainingDays > 10 && $remainingDays <= 15) {
                    $canChangeMembership = false;
                    $errorMessage = 'Debes esperar a que te queden 10 días o menos para renovar tu membresía.';
                }
                // Si no es la misma membresía y quedan menos de 10 días, solo puede renovar la misma
                elseif ($remainingDays <= 10 && $activeMembership->id !== $membresia->id) {
                    $canChangeMembership = false;
                    $errorMessage = 'Solo puedes renovar la misma membresía cuando te quedan menos de 10 días.';
                }
            } 
            // Si la membresía actual es gratuita, verificar saldo en capital
            else if ($activeMembership && $activeMembership->precio <= 0) {
                // Si tiene saldo en capital, no puede cambiar
                if ($user->capital_balance > 0) {
                    $canChangeMembership = false;
                    $errorMessage = 'No puedes cambiar de membresía mientras tengas saldo en tu cuenta de capital.';
                }
            }
        }
        
        // Si no puede cambiar de membresía, retornar error
        if (!$canChangeMembership) {
            Log::warning('Intento no permitido de cambio de membresía', [
                'user_id' => $user->id,
                'current_membership_id' => $user->membership_id,
                'requested_membership_id' => $membresia->id,
                'error' => $errorMessage
            ]);
            
            return redirect()->back()->with('error', $errorMessage);
        }
        
        Log::info('Iniciando activación de membresía gratuita', [
            'user_id' => $user->id,
            'membresia_id' => $membresia->id
        ]);
        
        DB::beginTransaction();
        
        try {
            // Si el usuario ya tiene una membresía activa, moverla al historial
            if ($user->membership_id) {
                $this->moveCurrentMembershipToHistory($user);
            }
            
            // Verificar si el usuario tiene saldo disponible
            $hasSufficientBalance = ($user->capital_balance > 0 || $user->earnings_balance > 0);
            
            // Actualizar la membresía del usuario
            DB::table('users')->where('id', $user->id)->update([
                'membership_id' => $membresia->id,
                'membership_expires_at' => null, // Membresías sin costo no tienen fecha de expiración
            ]);
            
            // Registrar en el historial de membresías
            MembershipHistory::create([
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'precio_pagado' => 0.00, // Gratuita
                'status' => 'activa',
                'payment_method' => 'N/A', // No aplicable (gratuita)
                'payment_reference' => 'Membresia gratuita',
                'activated_at' => now(),
                'expires_at' => null, // Sin fecha de expiración
                'notes' => 'Membresía activada directamente sin costo. ' . 
                          (!$hasSufficientBalance ? 'Usuario inactivo por falta de saldo.' : '')
            ]);
            
            // Registrar la activación directa de una membresía sin costo
            if ($user->sponsor_id) {
                $sponsor = User::find($user->sponsor_id);
                if ($sponsor) {
                    // Como la membresía es gratuita, no hay comisión para el sponsor
                    // pero podemos registrar la activación para fines de seguimiento
                    $sponsor->comisionesRecibidas()->create([
                        'user_id' => $user->id,
                        'membresia_id' => $membresia->id,
                        'monto' => 0,
                        'tipo' => 'directa'
                    ]);
                }
            }
            
            DB::commit();
            
            // Registrar activación exitosa
            Log::info('Membresía gratuita activada correctamente', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'has_balance' => $hasSufficientBalance
            ]);
            
            // Mostrar mensaje según el estado del usuario
            $message = 'Membresía activada correctamente';
            if (!$hasSufficientBalance) {
                $message .= '. Su cuenta está inactiva porque no tiene saldo. Realice un depósito para activarla.';
            }
            
            // Para Inertia, usamos redirect()->route() en lugar de to_route para mayor compatibilidad
            return redirect()->route('dashboard')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Mejorar el registro de errores con más detalles
            Log::error('Error al activar membresía gratuita', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Manejar el error para peticiones Inertia
            return redirect()->back()->with('error', 'Error al activar la membresía: ' . $e->getMessage());
        }
    }
    
    /**
     * Procesa el pago de una membresía con costo
     */
    public function pay(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'bank_name' => 'required|string|max:255',
            'transaction_reference' => 'required|string|max:255',
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string|max:1000',
            'is_renewal' => 'nullable|boolean',
        ]);
        
        $user = Auth::user();
        $membresia = Membresia::find($request->membresia_id);

        if (!$membresia) {
            return redirect()->route('dashboard')->with('error', 'Membresía no encontrada');
        }

        // Verificar que la membresía tenga costo
        if ($membresia->precio <= 0) {
            Log::warning('Intento de pagar una membresía gratuita', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id
            ]);
            
            return redirect()->back()->with('error', 'Esta membresía es gratuita y debe ser activada directamente.');
        }
        
        // Verificar que se haya subido la imagen
        if (!$request->hasFile('receipt_image')) {
            Log::warning('Intento de pago sin comprobante', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id
            ]);
            
            return redirect()->back()->with('error', 'No se ha proporcionado un comprobante de pago.');
        }
        
        // Verificar reglas de negocio para cambio/renovación de membresía
        $canChangeMembership = true;
        $canRenewMembership = true;
        $isExpired = false;
        $errorMessage = '';
        
        if ($user->membership_id) {
            $activeMembership = Membresia::find($user->membership_id);
            $remainingDays = $this->getRemainingDays($user);
            $isSameMembership = $activeMembership && $activeMembership->id === $membresia->id;
            
            // Verificar si la membresía actual está expirada
            $isExpired = $user->membership_expires_at && Carbon::parse($user->membership_expires_at)->isPast();
            
            // Si está expirada, permitir cambio sin restricciones
            if ($isExpired) {
                $canChangeMembership = true;
                $canRenewMembership = true;
                // Si es la misma, lo tratamos como renovación
                if ($isSameMembership) {
                    $isRenewal = true;
                }
                
                Log::info('Permitiendo pago de membresía porque la actual está expirada', [
                    'user_id' => $user->id,
                    'current_membership_id' => $user->membership_id,
                    'new_membership_id' => $membresia->id,
                    'expired_at' => $user->membership_expires_at,
                    'is_renewal' => $isRenewal ?? false
                ]);
            }
            // Si no está expirada y tiene costo, aplicar reglas
            else if ($activeMembership && $activeMembership->precio > 0) {
                // Para renovación de la misma membresía
                if ($isSameMembership) {
                    // Solo permitir renovación si quedan 10 días o menos
                    if ($remainingDays > 10) {
                        $canRenewMembership = false;
                        $errorMessage = 'Solo puedes renovar tu membresía cuando te queden 10 días o menos. ' .
                            'Actualmente te quedan ' . $remainingDays . ' días.';
                        return redirect()->back()->with('error', $errorMessage);
                    }
                    $isRenewal = true;
                } 
                // Para cambio a membresía diferente
                else {
                    // Si quedan más de 15 días, no puede cambiar
                    if ($remainingDays > 15) {
                        $canChangeMembership = false;
                        $errorMessage = 'No puedes cambiar de membresía cuando te quedan más de 15 días en tu membresía actual.';
                        return redirect()->back()->with('error', $errorMessage);
                    }
                    // Si quedan entre 10 y 15 días, tampoco puede cambiar
                    else if ($remainingDays > 10) {
                        $canChangeMembership = false;
                        $errorMessage = 'Solo puedes cambiar de membresía cuando te queden 10 días o menos.';
                        return redirect()->back()->with('error', $errorMessage);
                    }
                }
            }
            // Si la membresía actual es gratuita, verificar saldo en capital
            else if ($activeMembership && $activeMembership->precio <= 0) {
                // Si tiene saldo en capital, no puede cambiar
                if ($user->capital_balance > 0) {
                    $canChangeMembership = false;
                    $errorMessage = 'No puedes cambiar de membresía mientras tengas saldo en tu cuenta de capital.';
                    return redirect()->back()->with('error', $errorMessage);
                }
            }
        }
        
        // Si no puede cambiar de membresía y no es renovación, retornar error
        if (!$canChangeMembership) {
            Log::warning('Intento no permitido de cambio de membresía', [
                'user_id' => $user->id,
                'current_membership_id' => $user->membership_id,
                'requested_membership_id' => $membresia->id,
                'error' => $errorMessage
            ]);
            
            return redirect()->back()->with('error', $errorMessage);
        }
        
        // Si es renovación pero no cumple las condiciones
        if (($request->is_renewal ?? false) && !$canRenewMembership) {
            Log::warning('Intento no permitido de renovación de membresía', [
                'user_id' => $user->id,
                'current_membership_id' => $user->membership_id,
                'requested_membership_id' => $membresia->id,
                'remaining_days' => $remainingDays ?? null
            ]);
            
            return redirect()->back()->with('error', 'No cumples las condiciones para renovar esta membresía. Debe quedarte 10 días o menos en tu membresía actual.');
        }
        
        // Procesar el pago
        try {
            DB::beginTransaction();

            // Guardar la imagen del comprobante
            $receiptPath = $request->file('receipt_image')->store('membership_receipts', 'public');
            
            // Crear registro de pago
            $payment = new MembershipPayment();
            $payment->user_id = $user->id;
            $payment->membresia_id = $membresia->id;
            $payment->amount = $membresia->precio;
            $payment->bank_name = $request->bank_name;
            $payment->transaction_reference = $request->transaction_reference;
            $payment->receipt_image = $receiptPath;
            $payment->notes = $request->notes;
            $payment->status = 'pendiente'; // Pendiente de aprobación (valores permitidos: pendiente, aprobado, rechazado)
            $payment->processed_at = null; // Se actualizará cuando sea aprobado
            $payment->is_renewal = $request->is_renewal ?? false;
            $payment->save();
 
            // Actualizar la membresía del usuario
            $membershipUser = User::where('id', $user->id)->update([
                'pending_membership_id' => $membresia->id,
                'membership_expires_at' => null, // No aplicable (pendiente de aprobación)
            ]);
            
            // Comprobación adicional para asegurar que se actualizó correctamente
            $userUpdated = User::find($user->id);
            if ($userUpdated->pending_membership_id != $membresia->id) {
                Log::error('El campo pending_membership_id no se actualizó correctamente', [
                    'user_id' => $user->id,
                    'membresia_id' => $membresia->id,
                    'pending_membership_id_actual' => $userUpdated->pending_membership_id
                ]);
                
                // Forzar la actualización nuevamente
                $userUpdated->pending_membership_id = $membresia->id;
                $userUpdated->save();
                
                Log::info('Se intentó corregir el campo pending_membership_id', [
                    'user_id' => $user->id,
                    'membresia_id' => $membresia->id
                ]);
            }
            
            // Crear notificación para administradores
            $this->createAdminNotification(
                'Nueva solicitud de pago de membresía',
                'El usuario ' . $user->name . ' ha realizado un pago de membresía que requiere aprobación.',
                'membership_payment',
                $payment->id
            );
            
            // Enviar correo al usuario
            try {
                Mail::to($user->email)->send(new MembershipPaymentReceived($user, $membresia, $payment));
            } catch (\Exception $e) {
                Log::error('Error al enviar correo de pago recibido', [
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
                // No detenemos el proceso por un error de correo
            }
            
            // Registrar en el historial
            $historyStatus = 'pendiente';
            MembershipHistory::create([
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'precio_pagado' => $membresia->precio,
                'status' => $historyStatus,
                'payment_method' => 'manual', // Manual (con comprobante)
                'payment_reference' => $request->transaction_reference,
                'receipt_image' => $receiptPath,
                'notes' => $request->notes,
                'is_renewal' => $request->is_renewal ?? false,
                'activated_at' => now(), // Agregar la fecha de activación
            ]);
            
            DB::commit();
            
            Log::info('Pago de membresía registrado correctamente', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'payment_id' => $payment->id,
                'is_renewal' => $request->is_renewal ?? false
            ]);
            
            return redirect()->route('membership.history')->with('success', 
                'Tu pago ha sido registrado correctamente y está pendiente de aprobación. ' .
                'Recibirás una notificación cuando sea aprobado.'
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al procesar pago de membresía', [
                'user_id' => $user->id,
                'membresia_id' => $membresia->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 
                'Ha ocurrido un error al procesar tu pago. Por favor, intenta nuevamente o contacta al administrador.'
            );
        }
    }
    
    /**
     * Calcula los días restantes de la membresía de un usuario
     */
    private function getRemainingDays($user)
    {
        if (!$user->membership_expires_at) return null;
        
        $expirationDate = Carbon::parse($user->membership_expires_at);
        $now = Carbon::now();
        
        if ($now > $expirationDate) return 0;
        
        $diffTime = $expirationDate->diffInSeconds($now);
        return ceil($diffTime / (24 * 60 * 60));
    }
    
    /**
     * Administración de membresías pendientes
     */
    public function admin()
    {
        // Verificar permisos de administrador
        $user = Auth::user();
        // if (!$this->isAdmin($user)) {
        //     abort(403, 'No tiene permisos para acceder a esta sección.');
        // }
        
        // Primero cargar todas las membresías disponibles para tenerlas en caché
        $allMembresias = Membresia::all()->keyBy('id');
        
        // Obtener usuarios con membresías pendientes de aprobación
        // Añadir log para debug
        Log::info('Recuperando usuarios con membresías pendientes');
        
        $pendingMemberships = User::whereNotNull('pending_membership_id')
            ->get();
            
        Log::info('Usuarios con membresías pendientes encontrados', [
            'count' => $pendingMemberships->count(),
            'user_ids' => $pendingMemberships->pluck('id')->toArray(),
            'pending_membership_ids' => $pendingMemberships->pluck('pending_membership_id')->toArray()
        ]);

        // Cargar todos los usuarios que pueden ser sponsors
        $allUsers = User::whereIn('id', $pendingMemberships->pluck('sponsor_id')->filter())->get()->keyBy('id');
        
        // Obtener depósitos pendientes relacionados con membresías
        $pendingDeposits = Deposit::where('status', 'pendiente')
            ->whereNotNull('notes')
            ->where('notes', 'like', 'Pago de membresía:%')
            ->with(['user'])
            ->get();
            
        // Obtener pagos de membresía pendientes
        $membershipPayments = MembershipPayment::where('status', 'pendiente')
            ->get();
            
        Log::info('Pagos de membresía pendientes encontrados', [
            'count' => $membershipPayments->count(),
            'payment_ids' => $membershipPayments->pluck('id')->toArray()
        ]);
        
        // Obtener historial de membresías pendientes
        $membershipHistory = MembershipHistory::where('status', 'pendiente')
            ->get();
            
        Log::info('Historial de membresías pendientes encontrado', [
            'count' => $membershipHistory->count(),
            'history_ids' => $membershipHistory->pluck('id')->toArray()
        ]);
        
        // Enriquecer los datos de pendingMemberships con información adicional    
        foreach ($pendingMemberships as $pendingUser) {
            // Asignar la membresía directamente desde la colección cargada
            if ($pendingUser->pending_membership_id && $allMembresias->has($pendingUser->pending_membership_id)) {
                $pendingUser->pendingMembership = $allMembresias[$pendingUser->pending_membership_id];
            }
            
            // Asignar la información del sponsor manualmente
            if ($pendingUser->sponsor_id && $allUsers->has($pendingUser->sponsor_id)) {
                $sponsorUser = $allUsers[$pendingUser->sponsor_id];
                $pendingUser->sponsor = (object) [
                    'id' => $sponsorUser->id,
                    'name' => $sponsorUser->name,
                    'email' => $sponsorUser->email
                ];
            }
            
            // Buscar el pago asociado en membership_payments
            $payment = $membershipPayments->where('user_id', $pendingUser->id)
                ->where('membresia_id', $pendingUser->pending_membership_id)
                ->where('status', 'pendiente')
                ->first();
                
            // Buscar historial asociado en membership_history
            $history = $membershipHistory->where('user_id', $pendingUser->id)
                ->where('membresia_id', $pendingUser->pending_membership_id)
                ->where('status', 'pendiente')
                ->first();
            
            // Si no se encontró la membresía anteriormente, buscar en el historial
            if ((!$pendingUser->pendingMembership || !isset($pendingUser->pendingMembership->nombre)) && $history && $history->membresia_id) {
                // Intentar obtener de la colección de membresías
                if ($allMembresias->has($history->membresia_id)) {
                    $pendingUser->pendingMembership = $allMembresias[$history->membresia_id];
                }
            }
            
            // Si todavía no tenemos la membresía, hacer una última consulta directa
            if (!$pendingUser->pendingMembership || !isset($pendingUser->pendingMembership->nombre)) {
                // Consulta directa a la base de datos como último recurso
                $membresia = DB::table('membresias')->where('id', $pendingUser->pending_membership_id)->first();
                
                if ($membresia) {
                    // Convertir el objeto stdClass a un objeto Membresia
                    $pendingUser->pendingMembership = (object) [
                        'id' => $membresia->id,
                        'nombre' => $membresia->nombre,
                        'precio' => $membresia->precio,
                        'comision_directa' => $membresia->comision_directa ?? 0
                    ];
                }
            }
                
            // Combinar los datos de pago disponibles
            $pendingUser->membership_payment_info = [
                'payment_method' => $payment->bank_name ?? $history->payment_method ?? null,
                'payment_reference' => $payment->transaction_reference ?? $history->payment_reference ?? null,
                'receipt_image' => $payment->receipt_image ?? $history->receipt_image ?? null,
                'notes' => $payment->notes ?? $history->notes ?? null,
                'created_at' => $payment ? $payment->created_at : ($history ? $history->created_at : null)
            ];
        }
        
        return Inertia::render('admin/membership/Pending', [
            'pendingMemberships' => $pendingMemberships,
            'pendingDeposits' => $pendingDeposits
        ]);
    }
    
    /**
     * Aprobar la membresía pendiente de un usuario.
     */
    public function approve(User $user)
    {
        // Verificar si el usuario tiene una membresía pendiente
        if (!$user->pending_membership_id) {
            return redirect()->back()->with('error', 'El usuario no tiene una solicitud de membresía pendiente.');
        }
        
        $pendingMembership = Membresia::find($user->pending_membership_id);
        
        if (!$pendingMembership) {
            return redirect()->back()->with('error', 'La membresía solicitada no existe.');
        }
        
        // Buscar el pago de membresía pendiente
        $membershipPayment = MembershipPayment::where('user_id', $user->id)
            ->where('membresia_id', $pendingMembership->id)
            ->where('status', 'pendiente')
            ->latest()
            ->first();
        
        if (!$membershipPayment) {
            return redirect()->back()->with('error', 'No se encontró el pago correspondiente a esta membresía.');
        }
        
        // Buscar el historial de membresía pendiente para verificar si es renovación
        $membershipHistory = MembershipHistory::where('user_id', $user->id)
            ->where('membresia_id', $pendingMembership->id)
            ->where('status', 'pendiente')
            ->latest()
            ->first();
        
        $isRenewal = $membershipHistory && isset($membershipHistory->is_renewal) && $membershipHistory->is_renewal;
        
        Log::info('Iniciando aprobación de membresía', [
            'user_id' => $user->id,
            'membresia_id' => $pendingMembership->id,
            'payment_id' => $membershipPayment->id,
            'is_renewal' => $isRenewal
        ]);
        
        DB::beginTransaction();
        
        try {
            // Si es renovación de la misma membresía y el usuario ya tiene esa membresía activa
            if ($isRenewal && $user->membership_id === $pendingMembership->id) {
                // Determinar la nueva fecha de expiración
                $membershipDuration = 30; // Duración en días, podría ser un parámetro configurable
                
                // Si la membresía actual no ha expirado, sumar 30 días a la fecha actual de expiración
                if ($user->membership_expires_at && Carbon::parse($user->membership_expires_at)->isFuture()) {
                    $user->membership_expires_at = Carbon::parse($user->membership_expires_at)->addDays($membershipDuration);
                } else {
                    // Si ha expirado o no tiene fecha, establecer nueva fecha desde hoy
                    $user->membership_expires_at = Carbon::now()->addDays($membershipDuration);
                }
                
                $user->pending_membership_id = null;
                $user->save();
                
                Log::info('Membresía renovada correctamente', [
                    'user_id' => $user->id,
                    'membresia_id' => $pendingMembership->id,
                    'new_expires_at' => $user->membership_expires_at
                ]);
            } else {
                // Guardar el registro de membresía anterior en el historial si existe
                if ($user->membership_id) {
                    $this->moveCurrentMembershipToHistory($user);
                }
                
                // Actualizar la membresía del usuario
                $membershipDuration = 30; // Duración en días, podría ser un parámetro configurable
                
                $user->membership_id = $pendingMembership->id;
                $user->pending_membership_id = null;
                $user->membership_expires_at = Carbon::now()->addDays($membershipDuration);
                $user->save();
            }
            
            // Actualizar el estado del pago
            $membershipPayment->status = 'aprobado';
            $membershipPayment->processed_at = now();
            $membershipPayment->save();
            
            // Actualizar el historial de membresías
            if ($membershipHistory) {
                $membershipHistory->status = 'activa';
                $membershipHistory->activated_at = now();
                $membershipHistory->expires_at = $user->membership_expires_at;
                $membershipHistory->save();
            }
            
            // Procesar la comisión para el patrocinador si existe
            // Ahora se aplica tanto en nuevas membresías como en renovaciones
            if ($user->sponsor_id && $pendingMembership->comision_directa > 0) {
                $sponsor = User::find($user->sponsor_id);
                
                if ($sponsor) {
                    $comisionMonto = $pendingMembership->comision_directa;
                    
                    // Registrar la comisión
                    $comision = $sponsor->comisionesRecibidas()->create([
                        'user_id' => $user->id,
                        'membresia_id' => $pendingMembership->id,
                        'monto' => $comisionMonto,
                        'tipo' => 'directa'
                    ]);
                    
                    // Incrementar el balance de red del patrocinador
                    $sponsor->network_balance += $comisionMonto;
                    $sponsor->save();
                    
                    // Registrar la transacción
                    BalanceTransaction::create([
                        'user_id' => $sponsor->id,
                        'amount' => $comisionMonto,
                        'balance_type' => 'network',
                        'transaction_type' => 'commission',
                        'description' => $isRenewal 
                            ? "Comisión por renovación de membresía de {$user->name} - {$pendingMembership->nombre}" 
                            : "Comisión por referido {$user->name} - Membresía {$pendingMembership->nombre}",
                        'balance_before' => $sponsor->network_balance - $comisionMonto,
                        'balance_after' => $sponsor->network_balance,
                        'comision_id' => $comision->id
                    ]);
                    
                    // Registrar en el log
                    Log::info('Comisión por ' . ($isRenewal ? 'renovación' : 'referido') . ' procesada', [
                        'sponsor_id' => $sponsor->id,
                        'user_id' => $user->id,
                        'membresia_id' => $pendingMembership->id,
                        'monto' => $comisionMonto,
                        'comision_id' => $comision->id,
                        'is_renewal' => $isRenewal
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Membresía aprobada correctamente', [
                'user_id' => $user->id,
                'membresia_id' => $pendingMembership->id,
                'expires_at' => $user->membership_expires_at,
                'is_renewal' => $isRenewal
            ]);
            
            $message = $isRenewal 
                ? "Renovación de membresía {$pendingMembership->nombre} aprobada correctamente para {$user->name}."
                : "Membresía {$pendingMembership->nombre} aprobada correctamente para {$user->name}.";
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al aprobar membresía', [
                'user_id' => $user->id,
                'membresia_id' => $pendingMembership->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return redirect()->back()->with('error', "Error al aprobar la membresía: {$e->getMessage()}");
        }
    }
    
    /**
     * Rechaza una solicitud de membresía pendiente
     */
    public function reject(Request $request, User $user)
    {
        // Validar que haya un motivo de rechazo
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);
        
        // Verificar si el usuario tiene una membresía pendiente
        if (!$user->pending_membership_id) {
            return redirect()->back()->with('error', 'El usuario no tiene una solicitud de membresía pendiente.');
        }
        
        $pendingMembership = Membresia::find($user->pending_membership_id);
        
        if (!$pendingMembership) {
            return redirect()->back()->with('error', 'La membresía solicitada no existe.');
        }
        
        // Buscar el pago de membresía pendiente
        $membershipPayment = MembershipPayment::where('user_id', $user->id)
            ->where('membresia_id', $pendingMembership->id)
            ->where('status', 'pendiente')
            ->latest()
            ->first();
        
        if (!$membershipPayment) {
            return redirect()->back()->with('error', 'No se encontró el pago correspondiente a esta membresía.');
        }
        
        Log::info('Iniciando rechazo de membresía', [
            'user_id' => $user->id,
            'membresia_id' => $pendingMembership->id,
            'payment_id' => $membershipPayment->id,
            'reason' => $request->reason
        ]);
        
        DB::beginTransaction();
        
        try {
            // Actualizar el estado del pago
            $membershipPayment->status = 'rechazado';
            $membershipPayment->notes = ($membershipPayment->notes ? $membershipPayment->notes . "\n\n" : '') . 
                "Rechazado: " . $request->reason;
            $membershipPayment->processed_at = now();
            $membershipPayment->save();
            
            // Actualizar el historial de membresías
            $membershipHistory = MembershipHistory::where('user_id', $user->id)
                ->where('membresia_id', $pendingMembership->id)
                ->where('status', 'pendiente')
                ->latest()
                ->first();
            
            if ($membershipHistory) {
                $membershipHistory->status = 'rechazada';
                $membershipHistory->notes = ($membershipHistory->notes ? $membershipHistory->notes . "\n\n" : '') . 
                    "Motivo de rechazo: " . $request->reason;
                $membershipHistory->canceled_at = now();
                $membershipHistory->save();
            }
            
            // Limpiar la membresía pendiente del usuario
            $user->pending_membership_id = null;
            $user->save();
            
            DB::commit();
            
            Log::info('Membresía rechazada correctamente', [
                'user_id' => $user->id,
                'membresia_id' => $pendingMembership->id
            ]);
            
            return redirect()->back()->with('success', "Membresía {$pendingMembership->nombre} rechazada correctamente.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al rechazar membresía', [
                'user_id' => $user->id,
                'membresia_id' => $pendingMembership->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return redirect()->back()->with('error', "Error al rechazar la membresía: {$e->getMessage()}");
        }
    }
    
    /**
     * Muestra el historial de membresías del usuario.
     */
    public function history()
    {
        $user = Auth::user();
        
        // Obtener el historial de membresías del usuario con la relación a la membresía
        $membershipHistory = MembershipHistory::where('user_id', $user->id)
            ->with('membresia')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('Membership/History', [
            'membership_history' => $membershipHistory
        ]);
    }
    
    /**
     * Método auxiliar para mover la membresía actual al historial cuando se cambia de membresía
     */
    private function moveCurrentMembershipToHistory(User $user)
    {
        if (!$user->membership_id) {
            return;
        }
        
        $currentMembership = Membresia::find($user->membership_id);
        if (!$currentMembership) {
            return;
        }
        
        // Buscar si ya existe un registro activo en el historial
        $existingHistory = MembershipHistory::where('user_id', $user->id)
            ->where('membresia_id', $user->membership_id)
            ->where('status', 'activa')
            ->latest()
            ->first();
            
        if ($existingHistory) {
            // Actualizar el registro existente
            $existingHistory->status = 'expirada';
            $existingHistory->notes .= "\n\nReemplazada por nueva membresía el " . now()->format('d/m/Y H:i');
            $existingHistory->save();
        } else {
            // Crear un nuevo registro histórico (poco probable, pero por seguridad)
            MembershipHistory::create([
                'user_id' => $user->id,
                'membresia_id' => $user->membership_id,
                'precio_pagado' => $currentMembership->precio,
                'status' => 'expirada',
                'activated_at' => now()->subYear(), // Aproximado
                'expires_at' => now(),
                'notes' => 'Membresía anterior registrada automáticamente al cambiar de membresía. Datos aproximados.'
            ]);
        }
    }
    
    /**
     * Verificar si el usuario actual es administrador.
     */
    private function isAdmin()
    {
        return Auth::user() && Auth::user()->role === 'admin';
    }

    /**
     * Muestra el comprobante de pago de membresía.
     */
    public function showReceipt($membership)
    {
        $membershipHistory = MembershipHistory::findOrFail($membership);
        
        // Verificar que exista el comprobante
        if (!$membershipHistory->receipt_image || !Storage::disk('public')->exists($membershipHistory->receipt_image)) {
            abort(404, 'El comprobante no se encuentra disponible.');
        }
        
        // Obtener el contenido del archivo
        return response()->file(Storage::disk('public')->path($membershipHistory->receipt_image));
    }

    /**
     * Crea una notificación para los administradores
     */
    protected function createAdminNotification($title, $message, $type, $reference_id)
    {
        // Obtener usuarios administradores
        $admins = \App\Models\User::role('admin')->get();
        
        foreach ($admins as $admin) {
            $notification = new \App\Models\Notification();
            $notification->user_id = $admin->id;
            $notification->title = $title;
            $notification->message = $message;
            $notification->type = $type;
            $notification->reference_id = $reference_id;
            $notification->read = false;
            $notification->save();
        }
    }
} 