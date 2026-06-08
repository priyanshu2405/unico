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
        Schema::create('country_risk_levels', function (Blueprint $table) {
            $table->id();
            $table->string('country_name');
            $table->string('country_code', 5)->unique();
            $table->enum('risk_level', ['Low', 'Medium', 'High'])->default('Low');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_risk_levels');
    }
};
