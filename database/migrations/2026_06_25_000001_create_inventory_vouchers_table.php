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
        if (!Schema::hasTable('inventory_vouchers')) {
            Schema::create('inventory_vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('sku_id')->nullable();
                $table->string('brand_name')->nullable();
                $table->string('country_region')->nullable();
                $table->string('state')->nullable();
                $table->string('currency', 50)->nullable();
                $table->string('voucher_variant')->nullable();
                $table->string('voucher_type')->nullable();
                $table->string('purchase_invoice_no')->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->boolean('is_expired')->default(false);
                $table->string('logo')->nullable();
                $table->integer('quantity')->default(0);
                $table->integer('opening_stock_qty')->default(0);
                $table->integer('purchased_qty')->default(0);
                $table->decimal('purchase_value', 15, 2)->default(0.00);
                $table->decimal('purchase_value_per_unit', 15, 2)->default(0.00);
                $table->decimal('taxes', 15, 2)->default(0.00);
                $table->string('local_currency', 50)->nullable();
                $table->string('bank')->nullable();
                $table->decimal('currency_conversion_rate', 15, 6)->default(1.000000);
                $table->integer('referral_points_reseller')->default(0);
                $table->integer('agent_referral_points_per_unit')->default(0);
                $table->integer('agent_bonus_points_per_unit')->default(0);
                $table->decimal('agent_sale_price', 15, 2)->default(0.00);
                $table->integer('student_referral_points_per_unit')->default(0);
                $table->integer('student_bonus_points_per_unit')->default(0);
                $table->decimal('student_sale_price', 15, 2)->default(0.00);
                $table->longText('upload_vouchers')->nullable();
                $table->longText('delivered_vouchers')->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse nothing to protect local database.
    }
};
