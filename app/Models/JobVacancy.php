<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobVacancy extends Model
{
    protected $table = 'job_vacancies';

    protected $fillable = [
        'title',
        'description',
        'category',
        'country',
        'status',
    ];

    public function applications()
    {
        return $this->hasMany(JobapplicationModel::class, 'vacancy_id');
    }
}
