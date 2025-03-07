<?php

use Illuminate\Support\Facades\Auth;



if(!function_exists('user_can')) {
    function user_can(array $permissions): void
    {
        if (!Auth::check()) {
            abort(403, 'Acceso Prohibido.');
        }
    
        foreach ($permissions as $permission) {
            if (Auth::user()->can($permission)) {
                return;
            }
        }
    
        abort(403, 'Acceso Prohibido.');
    }
}

if(!function_exists('prueba')) {
    function prueba(): void
    {
        dd('holaaa');
    }
}
