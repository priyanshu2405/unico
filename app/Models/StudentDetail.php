<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDetail extends Model
{
    use HasFactory;

    protected $table = 'student_details';

    protected $fillable = [
        'user_id',
        'full_name',
        'dob',
        'id_type',
        'id_number',
        'primary_contact',
        'email',
        'whatsapp_number',
        'address',
        'city',
        'state',
        'country',
        'post_code',
        'exam_purpose',
        'highest_education',
        'passing_year',
        'preferred_countries',
        'bank_name',
        'bank_country',
        'account_number',
        'id_doc',
        'id_doc_final',
        'shufti_reference',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
