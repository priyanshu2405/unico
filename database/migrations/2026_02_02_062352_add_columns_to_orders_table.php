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
                $table->integer('quantity')->default(1)->after('amount');
            }
            if (!Schema::hasColumn('orders', 'referral_points')) {
                $table->integer('referral_points')->default(0)->after('points_earned');
            }
            if (!Schema::hasColumn('orders', 'user_role')) {
                $table->string('user_role')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'delivery_details')) {
                $table->text('delivery_details')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('delivery_details');
            }
            if (!Schema::hasColumn('orders', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'referral_points', 'user_role', 'delivery_details']);
            // Not dropping voucher_id if we didn't add it or if it was already there
        });
    }
};
