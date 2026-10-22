<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bReferralQualification extends Model
{
    protected $table = 'b2b_referral_qualifications';

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'target_amount',
        'qualifying_start_date',
        'qualifying_deadline',
        'total_eligible_purchases',
        'is_qualified',
        'qualified_at',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'total_eligible_purchases' => 'decimal:2',
        'is_qualified' => 'boolean',
        'qualifying_start_date' => 'datetime',
        'qualifying_deadline' => 'datetime',
        'qualified_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 100.0;
        }
        return min(100.0, round(($this->total_eligible_purchases / $this->target_amount) * 100, 2));
    }
}
