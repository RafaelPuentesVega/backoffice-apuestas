<?php

use App\Http\Controllers\Deposit\DepositController;
use Illuminate\Support\Facades\Route;

// Rutas para usuario normal
Route::middleware(['auth', 'verified' ])->group(function () {
    Route::get('/deposit', [DepositController::class, 'create'])->name('deposit.create');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/history', [DepositController::class, 'history'])->name('deposit.history');
    Route::get('/deposit/{deposit}/receipt', [DepositController::class, 'showReceipt'])->name('deposit.receipt');
});

// Rutas para administradores
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/deposits', [DepositController::class, 'admin'])->name('admin.deposits');
    Route::post('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('admin.deposits.approve');
    Route::post('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('admin.deposits.reject');
}); 