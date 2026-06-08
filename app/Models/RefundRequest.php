<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'reason',
        'status',
        'admin_note',
        'bank_details',
        'transaction_id',
        'user_transaction_id',
        'transaction_slip',
        'refund_receipt',
        'processed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
