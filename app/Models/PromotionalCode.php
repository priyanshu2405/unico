<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionalCode extends Model
{
    protected $table = 'promotional_codes';

    protected $fillable = [
        'code',
        'title',
        'discount_type',
        'discount_value',
        'currency',
        'min_order_amount',
        'max_discount',
        'starts_at',
        'expires_at',
        'usage_limit_per_user',
        'total_usage_limit',
        'times_used',
        'eligible_roles',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'eligible_roles' => 'array',
        'is_active' => 'boolean',
        'usage_limit_per_user' => 'integer',
        'total_usage_limit' => 'integer',
        'times_used' => 'integer',
    ];

    public function isValidFor(User $user, float $subtotal): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This promo code is no longer active.'];
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return ['valid' => false, 'message' => 'This promo code has not started yet.'];
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return ['valid' => false, 'message' => 'This promo code has expired.'];
        }

        if ($this->total_usage_limit && $this->times_used >= $this->total_usage_limit) {
            return ['valid' => false, 'message' => 'This promo code usage limit has been reached.'];
        }

        if (!empty($this->eligible_roles) && is_array($this->eligible_roles)) {
            if (!in_array($user->account_type, $this->eligible_roles)) {
                return ['valid' => false, 'message' => 'This code is not eligible for your account category.'];
            }
        }

        if ($this->min_order_amount > 0 && $subtotal < $this->min_order_amount) {
            return ['valid' => false, 'message' => 'Minimum order amount for this code is ' . number_format($this->min_order_amount, 2)];
        }

        // Check user usage limit
        $usedCount = Order::where('user_id', $user->id)
            ->where('promo_code_applied', $this->code)
            ->whereIn('status', ['completed', 'delivered', 'pending'])
            ->count();

        if ($usedCount >= $this->usage_limit_per_user) {
            return ['valid' => false, 'message' => 'You have already used this promo code.'];
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = ($subtotal * $this->discount_value) / 100;
        } else {
            $discount = $this->discount_value;
        }

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }
}
