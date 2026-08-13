<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_id',
        'support_user_id',
        'country',
        'region',
        'topic_id',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function supportUser()
    {
        return $this->belongsTo(User::class, 'support_user_id');
    }

    public function topic()
    {
        return $this->belongsTo(SupportOption::class, 'topic_id');
    }
}
