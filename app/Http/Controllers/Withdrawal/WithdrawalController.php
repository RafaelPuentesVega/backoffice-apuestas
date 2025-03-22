<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use App\Mail\WithdrawalVerification;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    /**
     * Muestra la vista para crear un retiro
     */
    public function create()
    {
        $user = Auth::user();
        
        // Verificar si el usuario tiene una billetera configurada
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        return Inertia::render('withdrawal/Create', [
            'hasWallet' => !is_null($wallet),
            'wallet' => $wallet
        ]);
    }

    /**
     * Muestra el historial de retiros
     */
    public function history()
    {
        $user = Auth::user();
        
        // Obtener los retiros del usuario
        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return Inertia::render('withdrawal/History', [
            'withdrawals' => $withdrawals
        ]);
    }

    /**
     * Genera un token de verificación y lo envía por correo
     */
    public function requestToken(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $user = Auth::user();
        
        // Verificar si el usuario tiene una billetera configurada
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return back()->withErrors([
                'message' => 'Debe configurar su billetera antes de realizar un retiro.'
            ]);
        }
        
        // Generar un código de verificación de 6 dígitos
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Guardar el código en la base de datos
        VerificationCode::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'withdrawal'],
            [
                'code' => $token,
                'expires_at' => now()->addMinutes(15) // Expira en 15 minutos
            ]
        );
        
        // Enviar el correo con el token de verificación
        Mail::to($user->email)->send(new WithdrawalVerification($token));
        
        // En lugar de devolver JSON, devolvemos una redirección con un mensaje flash
        return back()->with('success', 'Se ha enviado un código de verificación a su correo electrónico.');
    }

    /**
     * Procesa la solicitud de retiro
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'wallet_address' => 'required|string',
            'verification_token' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        
        // Verificar el token
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('type', 'withdrawal')
            ->where('code', $request->verification_token)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$verificationCode) {
            return back()->withErrors([
                'verification_token' => 'El código de verificación es inválido o ha expirado.'
            ]);
        }
        
        // Obtener el tipo de billetera del usuario
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return back()->withErrors([
                'message' => 'No se encontró una billetera configurada.'
            ]);
        }
        
        // Crear el registro de retiro
        Withdrawal::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'withdrawal_type' => $wallet->type, // Usar el tipo de la billetera del usuario
            'wallet_address' => $request->wallet_address,
            'description' => null,
            'status' => 'pendiente'
        ]);
        
        // Eliminar el código de verificación usado
        $verificationCode->delete();
        
        return redirect()->route('withdrawal.history')->with('success', 'Solicitud de retiro enviada correctamente.');
    }
}
