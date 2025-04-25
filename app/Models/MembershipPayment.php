<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPayment extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'membresia_id',
        'amount',
        'bank_name',
        'transaction_reference',
        'receipt_image',
        'notes',
        'is_renewal',
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
        'is_renewal' => 'boolean',
    ];

    /**
     * Obtiene el usuario al que pertenece este pago de membresía.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Obtiene la membresía relacionada con este pago.
     */
    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }
} 