<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Depósitos
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración relacionada con los depósitos
    | realizados por los usuarios en la plataforma.
    |
    */

    // Monto mínimo de depósito permitido
    'min_amount' => env('DEPOSIT_MIN_AMOUNT', 1000),
    
    // Monto máximo de depósito permitido
    'max_amount' => env('DEPOSIT_MAX_AMOUNT', 1000000),
    
    // Estado por defecto para nuevos depósitos
    'default_status' => 'pendiente',
    
    // Tipos de comprobantes permitidos
    'allowed_receipt_types' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
    
    // Tamaño máximo de archivo de comprobante en KB
    'max_receipt_size' => 2048, // 2MB
    
    // Duración del tiempo de aprobación en horas
    'approval_time' => 24,
]; 