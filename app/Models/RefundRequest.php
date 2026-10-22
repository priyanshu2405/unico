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
        'user_transaction_id',
        'transaction_slip',
        'transaction_id',
        'refund_receipt',
        'processed_at',
        'currency',
        'exchange_rate_applied',
        'amount_in_base_currency',
        'approved_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function messages()
    {
        return $this->hasMany(RefundChatMessage::class, 'refund_request_id');
    }
}

