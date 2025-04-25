<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use App\Mail\WithdrawalVerification;
use App\Models\Parameter;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    /**
     * Muestra la vista para crear un retiro
     */
    public function create()
    {
        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        // Verificar si el usuario ya tiene un retiro pendiente
        $hasPendingWithdrawal = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();
        
        // Obtener los días permitidos para retiro de cada tipo de saldo
        $withdrawalDaysCapital = Parameter::get('withdrawal_days_capital', [4]); // Jueves por defecto
        $withdrawalDaysEarnings = Parameter::get('withdrawal_days_earnings', [0]); // Domingo por defecto
        $withdrawalDaysNetwork = Parameter::get('withdrawal_days_network', [0,1,2,3,4,5,6]); // Todos los días por defecto
        
        // Obtener el día actual (0 = domingo, 1 = lunes, etc.)
        $currentDayOfWeek = Carbon::now()->dayOfWeek;
        
        // Verificar qué tipos de saldo se pueden retirar hoy
        $canWithdrawCapital = in_array($currentDayOfWeek, $withdrawalDaysCapital);
        $canWithdrawEarnings = in_array($currentDayOfWeek, $withdrawalDaysEarnings);
        $canWithdrawNetwork = in_array($currentDayOfWeek, $withdrawalDaysNetwork);
        
        // Obtener montos mínimos y máximos
        $minAmount = Parameter::get('withdrawal_min_amount', 50);
        $maxAmount = Parameter::get('withdrawal_max_amount', 5000);
        
        return Inertia::render('withdrawal/Create', [
            'hasWallet' => !is_null($wallet),
            'wallet' => $wallet,
            'hasPendingWithdrawal' => $hasPendingWithdrawal,
            'withdrawalRules' => [
                'capital' => [
                    'available' => $canWithdrawCapital,
                    'days' => $this->formatDays($withdrawalDaysCapital),
                    'balance' => $user->capital_balance
                ],
                'earnings' => [
                    'available' => $canWithdrawEarnings,
                    'days' => $this->formatDays($withdrawalDaysEarnings),
                    'balance' => $user->earnings_balance
                ],
                'network' => [
                    'available' => $canWithdrawNetwork,
                    'days' => $this->formatDays($withdrawalDaysNetwork),
                    'balance' => $user->network_balance
                ],
            ],
            'withdrawalLimits' => [
                'min' => $minAmount,
                'max' => $maxAmount,
            ],
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
            'amount' => 'required|numeric|min:1',
            'balance_type' => 'required|in:capital,earnings,network',
        ]);

        $user = Auth::user();
        $balanceType = $request->balance_type;
        $amount = $request->amount;
        
        // Verificar si ya tiene un retiro pendiente
        $hasPendingWithdrawal = Withdrawal::where('user_id', $user->id)
            ->where('status', 'pendiente')
            ->exists();
            
        if ($hasPendingWithdrawal) {
            return back()->withErrors([
                'message' => 'Ya tiene una solicitud de retiro pendiente. Debe esperar a que se procese.'
            ]);
        }
        
        // Verificar si se puede retirar hoy de este tipo de saldo
        $currentDayOfWeek = Carbon::now()->dayOfWeek;
        $withdrawalDays = [];
        
        if ($balanceType === 'capital') {
            $withdrawalDays = Parameter::get('withdrawal_days_capital', [4]);
            $balanceField = 'capital_balance';
        } elseif ($balanceType === 'earnings') {
            $withdrawalDays = Parameter::get('withdrawal_days_earnings', [0]);
            $balanceField = 'earnings_balance';
        } else {
            $withdrawalDays = Parameter::get('withdrawal_days_network', [0,1,2,3,4,5,6]);
            $balanceField = 'network_balance';
        }
        
        if (!in_array($currentDayOfWeek, $withdrawalDays)) {
            return back()->withErrors([
                'message' => 'Hoy no está permitido realizar retiros de este tipo de saldo. Días permitidos: ' . $this->formatDays($withdrawalDays)
            ]);
        }
        
        // Verificar saldo suficiente
        if ($user->$balanceField < $amount) {
            return back()->withErrors([
                'amount' => 'No tiene saldo suficiente para realizar este retiro.'
            ]);
        }
        
        // Verificar montos mínimos y máximos
        $minAmount = Parameter::get('withdrawal_min_amount', 50);
        $maxAmount = Parameter::get('withdrawal_max_amount', 5000);
        
        if ($amount < $minAmount) {
            return back()->withErrors([
                'amount' => "El monto mínimo de retiro es $minAmount."
            ]);
        }
        
        if ($amount > $maxAmount) {
            return back()->withErrors([
                'amount' => "El monto máximo de retiro es $maxAmount."
            ]);
        }
        
        $wallet = Wallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return back()->withErrors([
                'message' => 'Debe configurar su billetera antes de realizar un retiro.'
            ]);
        }
        
        // Guardar temporalmente el tipo de saldo a retirar en la sesión
        session(['withdrawal_balance_type' => $balanceType]);
        
        $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        VerificationCode::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'withdrawal'],
            [
                'code' => $token,
                'expires_at' => now()->addMinutes(15)
            ]
        );
        
        Mail::to($user->email)->send(new WithdrawalVerification($token));
        
        return back()->with('success', 'Se ha enviado un código de verificación a su correo electrónico.');
    }

    /**
     * Procesa la solicitud de retiro
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'verification_token' => 'required|string|size:6',
            'balance_type' => 'required|in:capital,earnings,network'
        ]);

        try {
            $user = Auth::user();
            $balanceType = $request->balance_type;
            $amount = $request->amount;
        
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
            
            // Verificar si ya tiene un retiro pendiente
            $hasPendingWithdrawal = Withdrawal::where('user_id', $user->id)
                ->where('status', 'pendiente')
                ->exists();
                
            if ($hasPendingWithdrawal) {
                return back()->withErrors([
                    'message' => 'Ya tiene una solicitud de retiro pendiente. Debe esperar a que se procese.'
                ]);
            }
            
            // Mapear el tipo de saldo al campo correspondiente en el modelo User
            $balanceField = '';
            if ($balanceType === 'capital') {
                $balanceField = 'capital_balance';
            } elseif ($balanceType === 'earnings') {
                $balanceField = 'earnings_balance';
            } else {
                $balanceField = 'network_balance';
            }
            
            // Verificar saldo suficiente
            if ($user->$balanceField < $amount) {
                return back()->withErrors([
                    'amount' => 'No tiene saldo suficiente para realizar este retiro.'
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
                'amount' => $amount,
                'withdrawal_type' => (string) $wallet->type,
                'wallet_address' => $wallet->address,
                'description' => "Retiro de saldo de {$balanceType}",
                'status' => 'pendiente'
            ]);
            
            // Reducir temporalmente el saldo del usuario (se reembolsará si se rechaza)
            User::where('id', $user->id)->update([
                $balanceField => DB::raw("{$balanceField} - {$amount}")
            ]);
            
            // Limpiar la sesión y el código de verificación
            session()->forget('withdrawal_balance_type');
            $verificationCode->delete();
            
            return redirect()->route('withdrawal.history')->with('success', 'Solicitud de retiro enviada correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'message' => 'Ha ocurrido un error al procesar la solicitud de retiro: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Formatea los días de la semana para mostrarlos al usuario
     */
    private function formatDays(array $days): string
    {
        $dayNames = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];
        
        $formattedDays = [];
        foreach ($days as $day) {
            $formattedDays[] = $dayNames[$day] ?? $day;
        }
        
        return implode(', ', $formattedDays);
    }
}
