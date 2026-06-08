<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherPriceRule extends Model
{
    protected $fillable = [
        'inventory_voucher_id',
        'purchase_id',
        'brand_name',
        'currency',
        'country_region',
        'voucher_variant',
        'voucher_type',
        'purchase_invoice_no',
        'purchase_date',
        'total_quantity',
        'purchase_value',
        'taxes',
        'per_unit_price',
        'issue_date',
        'credit_limit',
        'country_code',
        'country_name',
        'state',
        'city',
        'sale_price',
        'student_daily_limit',
        'agent_daily_limit',
        'reseller_daily_limit',
        'discount_type',
        'discount_value',
        'expiry_date',
        'is_active',
        'is_stopped',
        'is_brand_stopped',
        'is_country_stopped'
    ];

    public function inventoryVoucher(): BelongsTo
    {
        return $this->belongsTo(InventoryVoucher::class);
    }

    public function getFinalPriceAttribute()
    {
        if ($this->discount_type === 'percentage') {
            return $this->sale_price - ($this->sale_price * ($this->discount_value / 100));
        }
        return $this->sale_price - $this->discount_value;
    }
}
