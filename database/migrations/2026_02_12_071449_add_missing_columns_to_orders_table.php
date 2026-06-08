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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'voucher_id')) {
                $table->string('voucher_id')->nullable()->after('voucher_type');
            }
            if (!Schema::hasColumn('orders', 'quantity')) {
                $table->integer('quantity')->default(1)->after('voucher_id');
            }
            if (!Schema::hasColumn('orders', 'user_role')) {
                $table->string('user_role')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'referral_points')) {
                $table->integer('referral_points')->default(0)->after('user_role');
            }
            if (!Schema::hasColumn('orders', 'delivery_details')) {
                $table->text('delivery_details')->nullable()->after('referral_points');
            }
            if (!Schema::hasColumn('orders', 'admin_payment_method_id')) {
                $table->integer('admin_payment_method_id')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'payment_receipt')) {
                $table->string('payment_receipt')->nullable()->after('admin_payment_method_id');
            }
            if (!Schema::hasColumn('orders', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('orders', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable()->after('account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'voucher_id', 
                'quantity', 
                'user_role', 
                'referral_points', 
                'delivery_details', 
                'admin_payment_method_id', 
                'payment_receipt', 
                'account_number', 
                'ifsc_code'
            ]);
        });
    }
};
