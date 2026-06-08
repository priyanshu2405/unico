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
            $table->string('purchase_id')->nullable()->after('id');
            $table->string('brand_name')->nullable()->after('inventory_voucher_id');
            $table->string('currency')->nullable()->after('brand_name');
            $table->string('country_region')->nullable()->after('currency');
            $table->string('voucher_variant')->nullable()->after('country_region');
            $table->string('voucher_type')->nullable()->after('voucher_variant');
            $table->string('purchase_invoice_no')->nullable()->after('voucher_type');
            $table->date('purchase_date')->nullable()->after('purchase_invoice_no');
            $table->integer('total_quantity')->nullable()->after('purchase_date');
            $table->decimal('purchase_value', 15, 2)->nullable()->after('total_quantity');
            $table->decimal('taxes', 15, 2)->nullable()->after('purchase_value');
            $table->decimal('per_unit_price', 15, 2)->nullable()->after('taxes');
            $table->date('issue_date')->nullable()->after('per_unit_price');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_price_rules', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_id', 'brand_name', 'currency', 'country_region', 
                'voucher_variant', 'voucher_type', 'purchase_invoice_no', 
                'purchase_date', 'total_quantity', 'purchase_value', 
                'taxes', 'per_unit_price', 'issue_date', 'credit_limit'
            ]);
        });
    }
};
