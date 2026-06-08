<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherInventory extends Model
{
    protected $table = 'voucher_inventory';
    protected $fillable = ['voucher_id', 'voucher_code', 'is_used'];
}
