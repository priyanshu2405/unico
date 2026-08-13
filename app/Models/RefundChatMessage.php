<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundChatMessage extends Model
{
    protected $fillable = [
        'refund_request_id',
        'user_id',
        'message',
        'file_path',
        'file_type',
        'is_read',
    ];

    public function refundRequest(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class, 'refund_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
