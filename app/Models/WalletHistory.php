<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletHistory extends Model
{
    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'wallet_history';

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
     * Obtiene el usuario al que pertenece este registro de historial.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
