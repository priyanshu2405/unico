<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'is_active',
        'effective_from',
        'approved_by',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
