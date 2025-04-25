<?php

use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Withdrawal\WithdrawalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('login');
})->name('home');

Route::get('dashboard', [UserDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Rutas de dashboard de usuario
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
});

// Rutas de membresía
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/membership/select', [MembershipController::class, 'select'])->name('membership.select');
    Route::post('/membership/activate', [MembershipController::class, 'activate'])->name('membership.activate');
    Route::post('/membership/pay', [MembershipController::class, 'pay'])->name('membership.pay');
    Route::get('/membership/history', [MembershipController::class, 'history'])->name('membership.history');
    Route::get('/membership/{membership}/receipt', [MembershipController::class, 'showReceipt'])->name('membership.receipt');
});

// Rutas de transacciones y reportes
Route::middleware(['auth', 'verified', 'require.membership'])->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/network-commissions', [TransactionController::class, 'networkCommissions'])->name('transactions.network');
    Route::get('/transactions/report', [TransactionController::class, 'consolidatedReport'])->name('transactions.report');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/withdrawal.php';
require __DIR__.'/network.php';
require __DIR__.'/admin.php';
require __DIR__.'/deposit.php';
