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
        Schema::create('support_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('support_user_id')->constrained('users')->onDelete('cascade');
            $table->string('country');
            $table->string('region')->nullable();
            $table->foreignId('topic_id')->constrained('support_options')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_assignments');
    }
};
