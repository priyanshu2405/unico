<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportQuery extends Model
{
    protected $fillable = [
        'user_id',
        'topic',
        'issue',
        'description',
        'status',
        'country',
        'region',
        'assigned_to',
        'escalated_by',
        'escalation_level',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function escalatedBy()
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }
}
