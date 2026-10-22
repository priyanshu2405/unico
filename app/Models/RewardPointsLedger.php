<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardPointsLedger extends Model
{
    protected $table = 'reward_points_ledgers';

    protected $fillable = [
        'user_id',
        'order_id',
        'points',
        'points_remaining',
        'point_type',
        'status',
        'available_at',
        'expires_at',
        'expiry_notified_30d',
        'expiry_notified_7d',
        'description',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'points_remaining' => 'integer',
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
        'expiry_notified_30d' => 'boolean',
        'expiry_notified_7d' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
