<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Mail\WalletVerification;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WalletController extends Controller
{
    /**
     * Muestra la vista de configuración de billetera
     */
    public function edit()
    {
        $user = Auth::user();
        
        // Obtener la billetera del usuario si existe
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        // Obtener el historial de billeteras del usuario
        $walletHistory = WalletHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('settings/Wallet', [
            'wallet' => $wallet,
            'walletHistory' => $walletHistory
        ]);
    }

    /**
     * Obtiene la billetera del usuario para la API
     */
    public function getUserWallet()
    {
        $user = Auth::user();
        
        // Obtener la billetera del usuario si existe
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        return response()->json([
            'success' => true,
            'wallet' => $wallet ? $wallet->address : null
        ]);
    }

    /**
     * Genera un código de verificación para la configuración de billetera
     */
    public function requestVerification(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'wallet_type' => 'required|string'
        ]);

        $user = Auth::user();
        
        // Generar un código de verificación de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Guardar el código en la base de datos
        VerificationCode::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'wallet'],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(15) // Expira en 15 minutos
            ]
        );
        
        // Enviar el correo con el código de verificación
        Mail::to($user->email)->send(new WalletVerification($code));
        
        // return response()->json([
        //     'success' => true,
        //     'message' => 'Código de verificación enviado correctamente.'
        // ]);
    }

    /**
     * Actualiza la información de la billetera
     */
    public function update(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string',
            'wallet_type' => 'required|string',
            'verification_code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        
        // Verificar el código
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', 'wallet')
            ->where('code', $request->verification_code)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$verificationCode) {
            return back()->withErrors([
                'verification_code' => 'El código de verificación es inválido o ha expirado.'
            ]);
        }
        
        // Obtener la billetera actual si existe
        $currentWallet = Wallet::where('user_id', $user->id)->first();
        

            
        // Guardar en el historial
        WalletHistory::create([
            'user_id' => $user->id,
            'address' => $currentWallet->address,
            'type' => $currentWallet->type
        ]);
        
        
        // Actualizar o crear el registro de billetera
        Wallet::updateOrCreate(
            ['user_id' => $user->id],
            [
                'address' => $request->wallet_address,
                'type' => $request->wallet_type
            ]
        );
        
        // Eliminar el código de verificación usado
        $verificationCode->delete();
        
        return redirect()->route('settings.wallet')->with('success', 'Billetera configurada correctamente.');
    }
}
