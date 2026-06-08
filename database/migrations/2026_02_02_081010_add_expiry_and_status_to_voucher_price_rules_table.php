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
        Schema::table('voucher_price_rules', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('discount_value');
            $table->boolean('is_active')->default(true)->after('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_price_rules', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'is_active']);
        });
    }
};
