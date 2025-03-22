<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\WhatsappController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
    
    // WhatsApp routes
    Route::get('settings/whatsapp', [WhatsappController::class, 'edit'])->name('settings.whatsapp.edit');
    Route::post('settings/whatsapp', [WhatsappController::class, 'update'])->name('settings.whatsapp.update');

    // Rutas API para WhatsApp
    Route::prefix('api/whatsapp')->name('api.whatsapp.')->group(function () {
        Route::post('/request-verification', [WhatsappController::class, 'requestVerification'])->name('request-verification');
        Route::post('/verify-code', [WhatsappController::class, 'verifyCode'])->name('verify-code');
        Route::get('/check-history', [WhatsappController::class, 'checkHistory'])->name('check-history');
    });
});
