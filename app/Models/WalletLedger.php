<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedger extends Model
{
    protected $table = 'wallet_ledger';
    protected $fillable = [
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'source',
        'description',
        'ip_address',
        'user_agent'
    ];

    public $timestamps = true;
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
