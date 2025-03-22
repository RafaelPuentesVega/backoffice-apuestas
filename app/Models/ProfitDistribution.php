<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'wiltdraw_id',
        'user_id',
        'amount',
        'type',
        'is_processed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_processed' => 'boolean',
    ];

    public function wiltdraw()
    {
        return $this->belongsTo(Wiltdraw::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
