<?php

use App\Http\Controllers\Withdrawal\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('withdrawal', 'withdrawal/history');

    // Rutas para retiros
    Route::get('withdrawal/create', [WithdrawalController::class, 'create'])
        ->name('withdrawal.create');

    Route::post('withdrawal', [WithdrawalController::class, 'store'])
        ->name('withdrawal.store');

    Route::get('withdrawal/history', [WithdrawalController::class, 'history'])
        ->name('withdrawal.history');
}); 