<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportOption extends Model
{
    protected $fillable = ['parent_id', 'type', 'name', 'is_active'];

    public function parent()
    {
        return $this->belongsTo(SupportOption::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SupportOption::class, 'parent_id');
    }
}
