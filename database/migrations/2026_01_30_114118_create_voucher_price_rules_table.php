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
        Schema::create('voucher_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_voucher_id')->constrained('inventory_vouchers')->onDelete('cascade');
            $table->string('country_code');
            $table->string('country_name');
            $table->decimal('sale_price', 15, 2);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_price_rules');
    }
};
