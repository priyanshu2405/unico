<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    protected $table = 'memberships';

    protected $fillable = [
        'user_id',
        'plan_type',
        'fee_paid',
        'currency',
        'discount_percent',
        'promo_code',
        'starts_at',
        'expires_at',
        'status',
        'auto_renew',
        'total_discount_saved',
    ];

    protected $casts = [
        'fee_paid' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
        'total_discount_saved' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }
}
