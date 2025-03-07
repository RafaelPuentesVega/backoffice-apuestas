<?php

use App\Http\Controllers\Withdrawal\WithdrawalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/withdrawal.php';

// Route::middleware(['auth'])->group(function () {
//     Route::resource('withdrawals', WithdrawalController::class);
// });
