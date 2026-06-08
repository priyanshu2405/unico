<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDetail extends Model
{
    use HasFactory;

    protected $table = 'agent_details';

    protected $fillable = [
        'user_id',
        'agent_type',
        'business_name',
        'business_type',
        'registration_number',
        'business_contact',
        'business_email',
        'address',
        'city',
        'state',
        'country',
        'post_code',
        'website',
        'social_media',
        'representative_name',
        'dob',
        'id_type',
        'id_number',
        'designation',
        'whatsapp_number',
        'bank_name',
        'bank_country',
        'account_number',
        'registration_doc',
        'id_doc',
        'business_logo',
        'shufti_reference',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
