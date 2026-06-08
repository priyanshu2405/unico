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
            // Agent Specific
            $table->string('agent_type')->nullable()->after('zip_code');
            $table->string('business_name')->nullable()->after('agent_type');
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('registration_number')->nullable()->after('business_type');
            $table->string('business_contact')->nullable()->after('registration_number');
            $table->string('business_email')->nullable()->after('business_contact');
            $table->string('website')->nullable()->after('business_email');
            $table->string('social_media')->nullable()->after('website');
            $table->string('representative_name')->nullable()->after('social_media');
            $table->string('designation')->nullable()->after('representative_name');

            // Student Specific
            $table->string('exam_purpose')->nullable()->after('designation');
            $table->string('highest_education')->nullable()->after('exam_purpose');
            $table->year('passing_year')->nullable()->after('highest_education');
            $table->text('preferred_countries')->nullable()->after('passing_year');
            
            // Bank Details
            $table->string('bank_name')->nullable()->after('preferred_countries');
            $table->string('bank_country')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('bank_country');
            
            // Uploads & Verification
            $table->string('registration_doc')->nullable()->after('account_number');
            $table->string('id_doc')->nullable()->after('registration_doc');
            $table->string('id_doc_final')->nullable()->after('id_doc');
            $table->string('business_logo')->nullable()->after('id_doc_final');
            $table->string('shufti_reference')->nullable()->after('business_logo');
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
