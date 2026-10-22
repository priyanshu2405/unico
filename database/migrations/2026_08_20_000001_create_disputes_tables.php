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
        if (!Schema::hasTable('disputes')) {
            Schema::create('disputes', function (Blueprint $table) {
                $table->id();
                $table->string('dispute_id')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject');
                $table->text('description');
                $table->string('status')->default('open');
                $table->string('priority')->default('medium');
                $table->integer('rating')->nullable();
                $table->text('feedback')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dispute_messages')) {
            Schema::create('dispute_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispute_id')->constrained('disputes')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->string('attachment')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('disputes');
    }
};
