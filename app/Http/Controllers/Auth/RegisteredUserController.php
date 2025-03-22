<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WhatsappApiController;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\WhatsappHistory;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Envía un código de verificación al correo electrónico y WhatsApp del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'country_code' => 'required|string|max:6',
            'phone' => 'required|string|max:15',
            'sponsor_id' => 'required|string|max:25|exists:users,code_referral',
        ]);

        try {
            // Generar códigos de verificación diferentes para email y WhatsApp
            $emailCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $whatsappCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Guardar código de email en la base de datos
            VerificationCode::updateOrCreate(
                ['email' => $request->email],
                [
                    'code' => $emailCode,
                    'expires_at' => now()->addMinutes(5)
                ]
            );
            
            // Guardar código de WhatsApp en la sesión
            session([
                'whatsapp_verification_code' => $whatsappCode,
                'whatsapp_verification_expires_at' => now()->addMinutes(5),
                'phone' => $request->phone,
                'country_code' => $request->country_code
            ]);
            
            // Enviar código por email
            Mail::to($request->email)->send(new VerificationCodeMail($emailCode));
            
            // Enviar código por WhatsApp
            $whatsappApi = new WhatsappApiController();
            $whatsappResult = $whatsappApi->sendVerificationCode(
                $request->country_code,
                $request->phone,
                $whatsappCode
            );
            
            if (!$whatsappResult['success']) {
                Log::warning('No se pudo enviar el código de WhatsApp', [
                    'email' => $request->email,
                    'country_code' => $request->country_code,
                    'phone' => $request->phone,
                    'error' => $whatsappResult['message']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Se han enviado códigos de verificación a tu correo electrónico y WhatsApp.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar códigos de verificación', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'country_code' => $request->country_code,
                'phone' => $request->phone
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar los códigos de verificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica el código enviado al correo electrónico.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'code' => 'required|string|size:6',
        ]);

        $verificationCode = VerificationCode::where('email', $request->email)
            ->where('type', 'registration')
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un código de verificación para este correo.'
            ], 404);
        }

        if ($verificationCode->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'El código de verificación ha expirado. Por favor, solicita uno nuevo.'
            ], 400);
        }

        if ($verificationCode->code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'El código de verificación es incorrecto.'
            ], 400);
        }

        // Marcar como verificado en la sesión
        session(['email_verified' => true]);
        session(['verified_email' => $request->email]);

        return response()->json([
            'success' => true,
            'message' => 'Correo electrónico verificado correctamente.'
        ]);
    }

    /**
     * Verifica el código de WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyWhatsappCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

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

        // Marcar como verificado en la sesión
        session(['whatsapp_verified' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Código de WhatsApp verificado correctamente.'
        ]);
    }

    public function generateCodeReferral()
    {
        return  "REF-".Str::upper(Str::random(14, 'alnum'));
    }
    /**
     * Registra un nuevo usuario después de verificar el correo y WhatsApp.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country_code' => 'required|string|max:6',
            'phone' => 'required|string|max:15',
        ]);

        // Verificar que ambos códigos hayan sido validados
        if (!session('email_verified') || !session('whatsapp_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Debes verificar tanto tu correo electrónico como tu número de WhatsApp antes de registrarte.'
            ], 400);
        }

        // Verificar que el correo verificado coincida con el del formulario
        if (session('verified_email') !== $request->email) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico verificado no coincide con el del formulario.'
            ], 400);
        }

        // Verificar que el WhatsApp verificado coincida con el del formulario
        if (session('phone') !== $request->phone || 
            session('country_code') !== $request->country_code) {
            return response()->json([
                'success' => false,
                'message' => 'El número de WhatsApp verificado no coincide con el del formulario.'
            ], 400);
        }
        $code_referral = $this->generateCodeReferral();
        $sponsor = User::where('code_referral', $request->sponsor_id)->first();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'sponsor_id' => $request->sponsor_id,
            'country_code' => $request->country_code,
            'phone' => $request->phone,
            'whatsapp_number' => $request->phone,
            'code_referral' => $code_referral,
            'sponsor_id' => $sponsor->id,
        ]);
        event(new Registered($user));

        Auth::login($user);
        // Limpiar las variables de sesión
        session()->forget([
            'email', 
            'whatsapp_verified', 
            'verified_email',
            'whatsapp_verification_code',
            'whatsapp_verification_expires_at',
            'phone',
            'country_code'
        ]);
        
        return to_route('dashboard');
    }
}
