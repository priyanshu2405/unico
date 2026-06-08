<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    protected $table = 'pricing_rules';
    protected $fillable = ['voucher_id', 'country_code', 'base_price', 'discount_price'];
}
