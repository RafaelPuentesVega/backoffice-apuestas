<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'balance_type',
        'transaction_reference',
        'bank_name',
        'receipt_image',
        'notes',
        'status',
        'processed_at'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Obtiene el usuario al que pertenece este depósito.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 