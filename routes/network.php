<?php

use App\Http\Controllers\Network\NetworkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/network', [NetworkController::class, 'index'])->name('network.index');
});
