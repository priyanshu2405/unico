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
        Schema::create('student_details', function (Blueprint $row) {
            $row->id();
            $row->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal
            $row->string('full_name')->nullable();
            $row->date('dob')->nullable();
            $row->string('id_type')->nullable();
            $row->string('id_number')->nullable();
            
            // Contact
            $row->string('primary_contact')->nullable();
            $row->string('email')->nullable();
            $row->string('whatsapp_number')->nullable();
            
            // Address
            $row->text('address')->nullable();
            $row->string('city')->nullable();
            $row->string('state')->nullable();
            $row->string('country')->nullable();
            $row->string('post_code')->nullable();
            
            // Exam & Education
            $row->string('exam_purpose')->nullable();
            $row->string('highest_education')->nullable();
            $row->year('passing_year')->nullable();
            $row->text('preferred_countries')->nullable(); // JSON or comma-separated
            
            // Bank Details
            $row->string('bank_name')->nullable();
            $row->string('bank_country')->nullable();
            $row->string('account_number')->nullable();
            
            // Uploads
            $row->string('id_doc')->nullable();
            
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_details');
    }
};
