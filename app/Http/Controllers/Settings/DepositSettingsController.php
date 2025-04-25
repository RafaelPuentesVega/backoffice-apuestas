<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepositSettingsController extends Controller
{
    /**
     * Muestra el formulario para configurar los montos de depósito
     */
    public function edit()
    {
        // Obtener configuración actual
        $minAmount = Parameter::get('deposit_min_amount', 50);
        $maxAmount = Parameter::get('deposit_max_amount', 10000);
        
        return Inertia::render('admin/DepositSettings', [
            'depositSettings' => [
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
            ]
        ]);
    }
    
    /**
     * Actualiza la configuración de montos para depósitos
     */
    public function update(Request $request)
    {
        $request->validate([
            'min_amount' => 'required|numeric|min:1',
            'max_amount' => 'required|numeric|gt:min_amount',
        ]);
        
        // Actualizar parámetros
        Parameter::set(
            'deposit_min_amount', 
            $request->min_amount, 
            'Monto mínimo para solicitudes de depósito',
            'deposits'
        );
        
        Parameter::set(
            'deposit_max_amount', 
            $request->max_amount, 
            'Monto máximo para solicitudes de depósito',
            'deposits'
        );
        
        return back()->with('success', 'Configuración de depósitos actualizada correctamente');
    }
    
    /**
     * Restablece la configuración a valores predeterminados
     */
    public function reset()
    {
        // Restablecer a valores predeterminados
        Parameter::set(
            'deposit_min_amount', 
            50, 
            'Monto mínimo para solicitudes de depósito',
            'deposits'
        );
        
        Parameter::set(
            'deposit_max_amount', 
            10000, 
            'Monto máximo para solicitudes de depósito',
            'deposits'
        );
        
        return back()->with('success', 'Configuración de depósitos restablecida correctamente');
    }
} 