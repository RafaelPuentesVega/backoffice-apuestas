<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class VerificationCode extends Model
{
    protected $table = 'verification_codes';

    protected $fillable = ['user_id', 'email', 'type', 'code', 'expires_at'];
    
    protected $casts = [
        'expires_at' => 'datetime',
    ];
    
    /**
     * Obtiene el usuario asociado con este código de verificación
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}