<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'precio', 'comision_directa',
        'porcentaje_rendimiento', 'porcentaje_comision_sponsor',
        'status'
    ];

    public function comisiones()
    {
        return $this->hasMany(Comision::class);
    }
    
    public function users()
    {
        return $this->hasMany(User::class, 'membership_id');
    }

    // Verificar si la membresía tiene costo
    public function hasCost(): bool
    {
        return $this->precio > 0;
    }
}