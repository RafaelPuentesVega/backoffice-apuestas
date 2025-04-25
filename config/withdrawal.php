<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Retiros
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración relacionada con los retiros
    | solicitados por los usuarios en la plataforma.
    |
    */

    // Monto mínimo de retiro permitido
    'min_amount' => env('WITHDRAWAL_MIN_AMOUNT', 5000),
    
    // Monto máximo de retiro permitido
    'max_amount' => env('WITHDRAWAL_MAX_AMOUNT', 500000),
    
    // Estado por defecto para nuevos retiros
    'default_status' => 'pendiente',
    
    // Tiempo máximo de procesamiento en horas
    'processing_time' => 48,
    
    // Comisión por retiro (porcentaje)
    'fee_percentage' => env('WITHDRAWAL_FEE_PERCENTAGE', 0.5),
    
    // Comisión mínima por retiro
    'min_fee' => env('WITHDRAWAL_MIN_FEE', 100),
]; 