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
        Schema::create('agent_details', function (Blueprint $row) {
            $row->id();
            $row->foreignId('user_id')->constrained()->onDelete('cascade');
            $row->string('agent_type')->nullable();
            
            // Business Info
            $row->string('business_name')->nullable();
            $row->string('business_type')->nullable();
            $row->string('registration_number')->nullable();
            $row->string('business_contact')->nullable();
            $row->string('business_email')->nullable();
            
            // Business Address
            $row->text('address')->nullable();
            $row->string('city')->nullable();
            $row->string('state')->nullable();
            $row->string('country')->nullable();
            $row->string('post_code')->nullable();
            
            // Online Presence
            $row->string('website')->nullable();
            $row->string('social_media')->nullable();
            
            // Representative
            $row->string('representative_name')->nullable();
            $row->date('dob')->nullable();
            $row->string('id_type')->nullable();
            $row->string('id_number')->nullable();
            $row->string('designation')->nullable();
            $row->string('whatsapp_number')->nullable();
            
            // Bank Details
            $row->string('bank_name')->nullable();
            $row->string('bank_country')->nullable();
            $row->string('account_number')->nullable();
            
            // Uploads
            $row->string('registration_doc')->nullable();
            $row->string('id_doc')->nullable();
            $row->string('business_logo')->nullable();
            
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_details');
    }
};
