<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WhatsappApiController;
use App\Models\User;
use App\Models\WhatsappHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Display the WhatsApp settings page.
     *
     * @return \Inertia\Response
     */
    public function edit()
    {
        $user = Auth::user();
        // Get WhatsApp history for the user
        $whatsappHistory = WhatsappHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return Inertia::render('settings/Whatsapp', [
            'whatsapp' => [
                'number' => $user->phone,
                'country_code' => $user->country_code,
            ],
            'whatsappHistory' => $whatsappHistory,
        ]);
    }

    /**
     * Solicita un código de verificación para el número de WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function requestVerification(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|max:6',
            'phone' => 'required|string|max:15',
        ]);

        try {
            $whatsappApi = new WhatsappApiController();
            $result = $whatsappApi->verifyNumber($request->country_code, $request->phone);
            
            if ($result['success']) {
                // Guardar el código de verificación en la sesión
                session(['whatsapp_verification_code' => $result['verification_code']]);
                session(['whatsapp_verification_expires_at' => now()->addMinutes(10)]);
                session(['phone' => $request->phone]);
                session(['country_code' => $request->country_code]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Se ha enviado un código de verificación a tu número de WhatsApp.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo enviar el código de verificación: ' . $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error al solicitar verificación de WhatsApp', [
                'error' => $e->getMessage(),
                'country_code' => $request->country_code,
                'phone' => $request->phone
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar la verificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica el código de WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);
        
        // Verificar el código
        $storedCode = session('whatsapp_verification_code');
        $expiresAt = session('whatsapp_verification_expires_at');
        
        if (!$storedCode) {
            return response()->json([
                'success' => false,
                'message' => 'No se ha solicitado un código de verificación o la sesión ha expirado.'
            ], 400);
        }
        
        if ($expiresAt < now()) {
            return response()->json([
                'success' => false,
                'message' => 'El código de verificación ha expirado.'
            ], 400);
        }
        
        if ($request->code !== $storedCode) {
            return response()->json([
                'success' => false,
                'message' => 'El código de verificación es incorrecto.'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Código verificado correctamente.'
        ]);
    }

    /**
     * Update the user's WhatsApp number.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        // Verify the code
        $storedCode = session('whatsapp_verification_code');
        $phone = session('phone');
        $countryCode = session('country_code');
        
        if (!$storedCode || $request->verification_code !== $storedCode) {
            return back()->withErrors([
                'verification_code' => 'El código de verificación es inválido o ha expirado.'
            ])->withInput();
        }
        
        $user = Auth::user();
        

        WhatsappHistory::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'country_code' => $user->country_code,
        ]);
        
        // Update user's WhatsApp number using DB facade instead of save method
        User::where('id', $user->id)
            ->update([
                'phone' => $phone,
                'country_code' => $countryCode
            ]);
        
        // Clear session data
        session()->forget(['whatsapp_verification_code', 'phone', 'country_code', 'whatsapp_verification_expires_at']);
        
        return redirect()->route('settings.whatsapp.edit')->with('success', 'Número de WhatsApp actualizado correctamente.');
    }

    /**
     * Verifica y repara el historial de WhatsApp si es necesario.
     * Esta es una función de utilidad para depuración.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkHistory()
    {
        $user = Auth::user();
        
        // Obtener el historial actual
        $whatsappHistory = WhatsappHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Verificar si el usuario tiene un número de WhatsApp pero no tiene historial
        $needsHistoryCreation = $user->phone && $whatsappHistory->isEmpty();
        
        $result = [
            'user_id' => $user->id,
            'has_phone' => (bool)$user->phone,
            'phone' => $user->phone,
            'country_code' => $user->country_code,
            'history_count' => $whatsappHistory->count(),
            'history' => $whatsappHistory,
            'needs_history_creation' => $needsHistoryCreation
        ];
        
        // Si el usuario tiene un número de WhatsApp pero no tiene historial, crear uno
        if ($needsHistoryCreation) {
            try {
                $newHistory = WhatsappHistory::create([
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                ]);
                
                $result['history_created'] = true;
                $result['new_history'] = $newHistory;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Se ha creado un registro de historial para el número de WhatsApp actual.',
                    'data' => $result
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el historial: ' . $e->getMessage(),
                    'data' => $result
                ], 500);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'El historial de WhatsApp está en buen estado.',
            'data' => $result
        ]);
    }
}
