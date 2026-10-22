<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'agent_type' => 'string',
                'business_name' => 'string',
                'business_type' => 'string',
                'registration_number' => 'string',
                'business_contact' => 'string',
                'business_email' => 'string',
                'website' => 'string',
                'social_media' => 'string',
                'representative_name' => 'string',
                'designation' => 'string',
                'exam_purpose' => 'string',
                'highest_education' => 'string',
                'passing_year' => 'year',
                'preferred_countries' => 'text',
                'bank_name' => 'string',
                'bank_country' => 'string',
                'account_number' => 'string',
                'registration_doc' => 'string',
                'id_doc' => 'string',
                'id_doc_final' => 'string',
                'business_logo' => 'string',
                'shufti_reference' => 'string',
            ];

            foreach ($cols as $name => $type) {
                if (!Schema::hasColumn('users', $name)) {
                    if ($type === 'year') {
                        $table->year($name)->nullable();
                    } elseif ($type === 'text') {
                        $table->text($name)->nullable();
                    } else {
                        $table->string($name)->nullable();
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'agent_type',
                'business_name',
                'business_type',
                'registration_number',
                'business_contact',
                'business_email',
                'website',
                'social_media',
                'representative_name',
                'designation',
                'exam_purpose',
                'highest_education',
                'passing_year',
                'preferred_countries',
                'bank_name',
                'bank_country',
                'account_number',
                'registration_doc',
                'id_doc',
                'id_doc_final',
                'business_logo',
                'shufti_reference',
            ]);
        });
    }
};
