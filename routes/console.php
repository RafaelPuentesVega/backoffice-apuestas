<?php

use App\Console\Commands\CalculateDistributeProfits;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar el cálculo y distribución de ganancias diariamente
Schedule::command(CalculateDistributeProfits::class)->dailyAt('00:01')
    ->description('Calcula y distribuye las ganancias diarias de los usuarios');
