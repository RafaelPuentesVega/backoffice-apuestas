<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasFactory, Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sponsor_id',
        'code_referral',
        'phone',
        'whatsapp_number',
        'country_code',
        'capital_balance',
        'earnings_balance',
        'network_balance',
        'membership_type',
        'referrer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'capital_balance' => 'decimal:2',
            'earnings_balance' => 'decimal:2',
            'network_balance' => 'decimal:2',
        ];
    }

    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function referidos()
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    public function comisionesRecibidas()
    {
        return $this->hasMany(Comision::class, 'sponsor_id');
    }

    public function comisionesGeneradas()
    {
        return $this->hasMany(Comision::class, 'user_id');
    }
    
    /**
     * Obtiene la billetera del usuario.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
    
    /**
     * Obtiene los retiros del usuario.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
    
    /**
     * Obtiene los códigos de verificación del usuario.
     */
    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }
    
    /**
     * Obtiene el historial de números de WhatsApp del usuario.
     */
    public function whatsappHistory(): HasMany
    {
        return $this->hasMany(WhatsappHistory::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function profitDistributions()
    {
        return $this->hasMany(ProfitDistribution::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }
}
