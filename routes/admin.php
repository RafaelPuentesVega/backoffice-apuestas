<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\MembershipStatsController;
use App\Http\Controllers\MembershipController as MainMembershipController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Settings\WithdrawalSettingsController;
use App\Http\Controllers\Settings\DepositSettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified' ])->prefix('admin')->group(function () {
    // Panel de administración
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('admin.dashboard');
    
    // Rutas para usuarios
    Route::controller(UserController::class)->prefix('users')->name('admin.users.')->group(function () {
        Route::get('', 'index')->name('all');
        Route::get('/{id}', 'show')->name('show');
    });
    
    // Rutas para gestión de retiros
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])
        ->name('admin.withdrawals.index');
        
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])
        ->name('admin.withdrawals.show');
        
    Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])
        ->name('admin.withdrawals.approve');
        
    Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])
        ->name('admin.withdrawals.reject');
    
    // Rutas para configuración de retiros
    Route::get('/settings/withdrawals', [WithdrawalSettingsController::class, 'edit'])
        ->name('admin.settings.withdrawals');
    
    Route::post('/settings/withdrawals/update', [WithdrawalSettingsController::class, 'update'])
        ->name('admin.settings.withdrawals.update');
    
    Route::post('/settings/withdrawals/reset', [WithdrawalSettingsController::class, 'reset'])
        ->name('admin.settings.withdrawals.reset');
        
    // Rutas para configuración de depósitos
    Route::get('/settings/deposits', [DepositSettingsController::class, 'edit'])
        ->name('admin.settings.deposits');
    
    Route::post('/settings/deposits/update', [DepositSettingsController::class, 'update'])
        ->name('admin.settings.deposits.update');
    
    Route::post('/settings/deposits/reset', [DepositSettingsController::class, 'reset'])
        ->name('admin.settings.deposits.reset');
    
    // Rutas para gestión de membresías
    Route::get('/memberships', [MembershipController::class, 'index'])
        ->name('admin.memberships.index');
        
    Route::get('/memberships/create', [MembershipController::class, 'create'])
        ->name('admin.memberships.create');
        
    Route::post('/memberships', [MembershipController::class, 'store'])
        ->name('admin.memberships.store');
        
    Route::get('/memberships/{membership}/edit', [MembershipController::class, 'edit'])
        ->name('admin.memberships.edit');
        
    Route::put('/memberships/{membership}', [MembershipController::class, 'update'])
        ->name('admin.memberships.update');
        
    Route::delete('/memberships/{membership}', [MembershipController::class, 'destroy'])
        ->name('admin.memberships.destroy');
        
    // Gestión de membresías pendientes
    Route::get('/memberships/pending', [MainMembershipController::class, 'admin'])
        ->name('admin.memberships.pending');
        
    Route::post('/memberships/approve/{user}', [MainMembershipController::class, 'approve'])
        ->name('admin.memberships.approve');
        
    Route::post('/memberships/reject/{user}', [MainMembershipController::class, 'reject'])
        ->name('admin.memberships.reject');
    
    // Estadísticas y pagos de membresías
    Route::get('/memberships/stats', [MembershipStatsController::class, 'index'])
        ->name('admin.memberships.stats');
        
    Route::get('/memberships/payments', [MembershipStatsController::class, 'payments'])
        ->name('admin.memberships.payments');
        
    // Rutas para transacciones
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('admin.transactions.index');
}); 