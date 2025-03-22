<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'precio', 'comision_directa',
        'porcentaje_rendimiento', 'porcentaje_comision_sponsor'
    ];

    public function comisiones()
    {
        return $this->hasMany(Comision::class);
    }
}