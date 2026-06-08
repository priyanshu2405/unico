<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vouchar extends Model
{
    use HasFactory;
    protected $table = 'vouchers';

    protected $fillable = [
        'voucher_id',
        'name',
        'category',
        'price',
        'original_price',
        'stock',
        'quarterly_points',
        'yearly_points',
        'logo',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
        'stock' => 'integer',
        'quarterly_points' => 'integer',
        'yearly_points' => 'integer',
    ];
}
