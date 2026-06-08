<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobapplicationModel extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'vacancy_id',
        'name',
        'dob',
        'email',
        'phone',
        'whatsapp_number',
        'social_link',
        'address',
        'city',
        'state',
        'country',
        'post_code',
        'reference_name',
        'organization_name',
        'reference_email',
        'reference_phone',
        'id_type',
        'id_number',
        'designation',
        'bank_name',
        'bank_country',
        'bank_account_number',
        'id_document',
        'photograph',
        'reference_letter',
        'status',
    ];

    public function vacancy()
    {
        return $this->belongsTo(JobVacancy::class, 'vacancy_id');
    }
}
