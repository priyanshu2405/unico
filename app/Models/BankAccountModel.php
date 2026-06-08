<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccountModel extends Model
{

    // use HasFactory;

    protected $table = 'bank_accounts';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'account_type',
        'balance',
        'is_verified',
    ];

    /**
     * Type casting
     */
    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Relationship: Bank belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
