<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalSettingsController extends Controller
{
    /**
     * Muestra el formulario para configurar los días de retiro
     */
    public function edit()
    {
        // Obtener configuración actual
        $withdrawalDaysCapital = Parameter::get('withdrawal_days_capital', [4]);
        $withdrawalDaysEarnings = Parameter::get('withdrawal_days_earnings', [0]);
        $withdrawalDaysNetwork = Parameter::get('withdrawal_days_network', [0,1,2,3,4,5,6]);
        $minAmount = Parameter::get('withdrawal_min_amount', 50);
        $maxAmount = Parameter::get('withdrawal_max_amount', 5000);
        
        return Inertia::render('admin/WithdrawalSettings', [
            'withdrawalSettings' => [
                'capital_days' => $withdrawalDaysCapital,
                'earnings_days' => $withdrawalDaysEarnings,
                'network_days' => $withdrawalDaysNetwork,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
            ]
        ]);
    }
    
    /**
     * Actualiza la configuración de días y montos para retiros
     */
    public function update(Request $request)
    {
        $request->validate([
            'capital_days' => 'required|array',
            'capital_days.*' => 'numeric|min:0|max:6',
            'earnings_days' => 'required|array',
            'earnings_days.*' => 'numeric|min:0|max:6',
            'network_days' => 'required|array',
            'network_days.*' => 'numeric|min:0|max:6',
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|gt:min_amount',
        ]);
        
        // Actualizar parámetros
        Parameter::set(
            'withdrawal_days_capital', 
            $request->capital_days, 
            'Días permitidos para retiro de capital',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_days_earnings', 
            $request->earnings_days, 
            'Días permitidos para retiro de ganancias',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_days_network', 
            $request->network_days, 
            'Días permitidos para retiro de red',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_min_amount', 
            $request->min_amount, 
            'Monto mínimo para solicitudes de retiro',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_max_amount', 
            $request->max_amount, 
            'Monto máximo para solicitudes de retiro',
            'withdrawals'
        );
        
        return back()->with('success', 'Configuración de retiros actualizada correctamente');
    }
    
    /**
     * Restablece la configuración a valores predeterminados
     */
    public function reset()
    {
        // Restablecer a valores predeterminados
        Parameter::set(
            'withdrawal_days_capital', 
            [4], 
            'Días permitidos para retiro de capital',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_days_earnings', 
            [0], 
            'Días permitidos para retiro de ganancias',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_days_network', 
            [0,1,2,3,4,5,6], 
            'Días permitidos para retiro de red',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_min_amount', 
            50, 
            'Monto mínimo para solicitudes de retiro',
            'withdrawals'
        );
        
        Parameter::set(
            'withdrawal_max_amount', 
            5000, 
            'Monto máximo para solicitudes de retiro',
            'withdrawals'
        );
        
        return back()->with('success', 'Configuración de retiros restablecida correctamente');
    }
} 