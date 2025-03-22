<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'address',
        'type',
    ];

    /**
     * Obtiene el usuario al que pertenece esta billetera.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
