<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPaymentMethod extends Model
{
    protected $fillable = [
        'method_type',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'qr_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
