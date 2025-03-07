<?php

namespace App\Http\Controllers\Withdrawal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalController extends Controller
{
    /**
     * Mostrar el formulario para crear un nuevo retiro.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('withdrawal/Create');
    }

    /**
     * Mostrar el historial de retiros.
     *
     * @return \Inertia\Response
     */
    public function history()
    {
        // Lista fake de retiros para demostración
        $withdrawals = [
            [
                'id' => 1,
                'amount' => 1500.00,
                'status' => 'completado',
                'date' => '2023-10-15',
                'user' => 'Juan Pérez',
                'account' => '****4567'
            ],
            [
                'id' => 2,
                'amount' => 2300.50,
                'status' => 'pendiente',
                'date' => '2023-10-18',
                'user' => 'María González',
                'account' => '****7890'
            ],
            [
                'id' => 3,
                'amount' => 800.75,
                'status' => 'rechazado',
                'date' => '2023-10-20',
                'user' => 'Carlos Rodríguez',
                'account' => '****1234'
            ],
            [
                'id' => 4,
                'amount' => 3500.00,
                'status' => 'completado',
                'date' => '2023-10-22',
                'user' => 'Ana Martínez',
                'account' => '****5678'
            ],
            [
                'id' => 5,
                'amount' => 1200.25,
                'status' => 'pendiente',
                'date' => '2023-10-25',
                'user' => 'Roberto Sánchez',
                'account' => '****9012'
            ]
        ];

        return Inertia::render('withdrawal/History', [
            'withdrawals' => $withdrawals
        ]);
    }

    /**
     * Procesar la solicitud de retiro.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Aquí iría la lógica para procesar el retiro
        // Por ahora solo redireccionamos al historial

        return redirect()->route('withdrawal.history')->with('success', 'Solicitud de retiro enviada correctamente');
    }
} 