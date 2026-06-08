<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InventoryOutOfStockNotification;

class InventoryVoucher extends Model
{
    protected $table = 'inventory_vouchers';

    protected static function booted()
    {
        static::updating(function ($inventory) {
            if ($inventory->isDirty('quantity') && $inventory->quantity <= 0) {
                $inventory->status = 'OUT OF STOCK';
            }
        });

        static::updated(function ($inventory) {
            if ($inventory->isDirty('quantity') && $inventory->quantity <= 0 && $inventory->getOriginal('quantity') > 0) {
                $staff = User::whereIn('account_type', ['admin', 'manager'])->get();
                Notification::send($staff, new InventoryOutOfStockNotification($inventory));
            }
        });
    }

    protected $fillable = [
        'sku_id',
        'brand_name',
        'country_region',
        'state',
        'currency',
        'voucher_variant',
        'voucher_type',
        'purchase_invoice_no',
        'purchase_date',
        'expiry_date',
        'is_expired',
        'logo',
        'quantity',
        'purchase_value',
        'purchase_value_per_unit',
        'taxes',
        'local_currency',
        'bank',
        'currency_conversion_rate',
        'referral_points_reseller',
        'agent_referral_points_per_unit',
        'agent_bonus_points_per_unit',
        'agent_sale_price',
        'student_referral_points_per_unit',
        'student_bonus_points_per_unit',
        'student_sale_price',



        'upload_vouchers',
        'delivered_vouchers',
        'opening_stock_qty',
        'purchased_qty',
        'status'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiry_date' => 'date',
        'is_expired' => 'boolean',
        'quantity' => 'integer',
        'opening_stock_qty' => 'integer',
        'purchased_qty' => 'integer',
        'purchase_value' => 'decimal:2',
        'purchase_value_per_unit' => 'decimal:2',
        'taxes' => 'decimal:2',
        'currency_conversion_rate' => 'decimal:6',
        'referral_points_reseller' => 'integer',
        'agent_referral_points_per_unit' => 'integer',
        'agent_bonus_points_per_unit' => 'integer',
        'agent_sale_price' => 'decimal:2',
        'student_referral_points_per_unit' => 'integer',
        'student_bonus_points_per_unit' => 'integer',
        'student_sale_price' => 'decimal:2',
        'country_region' => 'array',
           'state' => 'array',
        'upload_vouchers' => 'array',
        'delivered_vouchers' => 'array',
    ];

    public function getLogoAttribute($value)
    {
        if (!$value) return null;
        if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
        return \Illuminate\Support\Facades\Storage::url($value);
    }

    public function scopeExpired($query)
    {
        return $query->where('is_expired', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_expired', false);
    }

    public function scopeCheckExpiry($query)
    {
        return $query->where('expiry_date', '<=', Carbon::now()->toDateString())
            ->where('is_expired', false);
    }

    public function isExpired()
    {
        return $this->expiry_date && Carbon::parse($this->expiry_date)->isPast();
    }

    public function updateExpiryStatus()
    {
        if ($this->isExpired()) {
            $this->update(['is_expired' => true]);
        } else {
            $this->update(['is_expired' => false]);
        }
    }

    public static function updateAllExpiredVouchers()
    {
        $expiredVouchers = self::checkExpiry()->get();
        foreach ($expiredVouchers as $voucher) {
            $voucher->update(['is_expired' => true]);
        }
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'voucher_id', 'sku_id');
    }
}
