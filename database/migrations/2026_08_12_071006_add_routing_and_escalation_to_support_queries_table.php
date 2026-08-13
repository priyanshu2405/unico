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
        Schema::table('support_queries', function (Blueprint $table) {
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('escalated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('escalation_level', ['none', 'peer', 'senior', 'manager'])->default('none');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_queries', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['escalated_by']);
            $table->dropColumn(['country', 'region', 'assigned_to', 'escalated_by', 'escalation_level']);
        });
    }
};
