<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipHistory extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'membership_history';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'membresia_id',
        'precio_pagado',
        'status',
        'payment_method',
        'payment_reference',
        'receipt_image',
        'activated_at',
        'expires_at',
        'canceled_at',
        'notes',
        'is_renewal'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'precio_pagado' => 'decimal:2',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'canceled_at' => 'datetime',
        'is_renewal' => 'boolean',
    ];

    /**
     * Obtiene el usuario al que pertenece este historial de membresía.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene la membresía relacionada con este historial.
     */
    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }

    /**
     * Verifica si la membresía está activa.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'activa') {
            return false;
        }

        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica si la membresía ha expirado.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    /**
     * Calcula los días restantes de la membresía.
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        $now = now();
        
        if ($now->greaterThan($this->expires_at)) {
            return 0;
        }

        return $now->diffInDays($this->expires_at);
    }
} 