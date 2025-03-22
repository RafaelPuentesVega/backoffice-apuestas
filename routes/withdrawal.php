<?php

use App\Http\Controllers\Withdrawal\WithdrawalController;
use App\Http\Controllers\Wallet\WalletController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas para retiros
    Route::get('/withdrawal/create', [WithdrawalController::class, 'create'])
        ->name('withdrawal.create');

    Route::get('/withdrawal/history', [WithdrawalController::class, 'history'])
        ->name('withdrawal.history');

    // Rutas para API de retiros
    Route::post('/api/withdrawal/request-token', [WithdrawalController::class, 'requestToken'])
        ->name('api.withdrawal.request-token');
    
    Route::post('/withdrawal/store', [WithdrawalController::class, 'store'])
        ->name('withdrawal.store');

    // Rutas para billetera
    Route::get('/settings/wallet', [WalletController::class, 'edit'])
        ->name('settings.wallet');

    Route::post('/settings/wallet/update', [WalletController::class, 'update'])
        ->name('settings.wallet.update');

    Route::post('/wallet/request-verification', [WalletController::class, 'requestVerification'])
        ->name('api.wallet.request-verification');

    Route::get('/api/user/wallet', [WalletController::class, 'getUserWallet'])
        ->name('api.user.wallet');
});
