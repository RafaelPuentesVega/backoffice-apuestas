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
        'membership_id',
        'pending_membership_id',
        'membership_expires_at',
        'sponsor_id',
        'is_admin',
        'code_referral',
        'phone',
        'whatsapp_number',
        'country_code',
        'capital_balance',
        'earnings_balance',
        'network_balance',
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
     * Obtiene los depósitos del usuario.
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
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

    public function membership()
    {
        return $this->belongsTo(Membresia::class, 'membership_id');
    }

    public function pendingMembership()
    {
        return $this->belongsTo(Membresia::class, 'pending_membership_id');
    }

    /**
     * Obtiene el historial de membresías del usuario.
     */
    public function membershipHistory(): HasMany
    {
        return $this->hasMany(MembershipHistory::class);
    }

    /**
     * Determine if the user's membership has expired.
     *
     * @return bool
     */
    public function hasMembershipExpired(): bool
    {
        if ($this->membership_expires_at === null) {
            return false;
        }

        return now()->greaterThan($this->membership_expires_at);
    }

    /**
     * Get the days remaining until membership expiration.
     *
     * @return int|null
     */
    public function getMembershipRemainingDays(): ?int
    {
        if ($this->membership_expires_at === null) {
            return null;
        }

        $expiresAt = \Carbon\Carbon::parse($this->membership_expires_at);
        $now = now();

        if ($now->greaterThan($expiresAt)) {
            return 0;
        }

        return $now->diffInDays($expiresAt);
    }

    /**
     * Extend membership by specified number of days.
     *
     * @param int $days
     * @return void
     */
    public function extendMembership(int $days): void
    {
        $currentExpiration = $this->membership_expires_at ? \Carbon\Carbon::parse($this->membership_expires_at) : now();
        
        // If membership has expired, extend from now
        if ($currentExpiration->lessThan(now())) {
            $currentExpiration = now();
        }
        
        $this->membership_expires_at = $currentExpiration->addDays($days);
        $this->save();
    }
}
