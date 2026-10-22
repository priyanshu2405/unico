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
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('currency', 10)->nullable()->after('amount');
            $table->decimal('exchange_rate_applied', 14, 6)->nullable()->after('currency');
            $table->decimal('amount_in_base_currency', 15, 2)->nullable()->after('exchange_rate_applied');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });

        Schema::table('wallet_ledger', function (Blueprint $table) {
            $table->string('currency', 10)->nullable()->after('amount');
            $table->decimal('original_amount', 15, 2)->nullable()->after('currency');
            $table->decimal('exchange_rate_applied', 14, 6)->nullable()->after('original_amount');
            $table->decimal('amount_usd', 15, 2)->nullable()->after('exchange_rate_applied');
        });

        Schema::table('inventory_vouchers', function (Blueprint $table) {
            $table->decimal('purchase_value_usd', 15, 2)->nullable()->after('purchase_value');
            $table->decimal('purchase_value_per_unit_usd', 15, 4)->nullable()->after('purchase_value_per_unit');
            $table->decimal('taxes_usd', 15, 2)->nullable()->after('taxes');
            $table->foreignId('rate_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('currency_conversion_rate');
        });

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->timestamp('effective_from')->nullable()->after('rate');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['currency', 'exchange_rate_applied', 'amount_in_base_currency', 'approved_by']);
        });

        Schema::table('wallet_ledger', function (Blueprint $table) {
            $table->dropColumn(['currency', 'original_amount', 'exchange_rate_applied', 'amount_usd']);
        });

        Schema::table('inventory_vouchers', function (Blueprint $table) {
            $table->dropForeign(['rate_approved_by']);
            $table->dropColumn(['purchase_value_usd', 'purchase_value_per_unit_usd', 'taxes_usd', 'rate_approved_by']);
        });

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['effective_from', 'approved_by']);
        });
    }
};
