<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'promo_code_applied')) {
                $table->string('promo_code_applied', 50)->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'promo_discount_amount')) {
                $table->decimal('promo_discount_amount', 15, 2)->default(0.00)->after('promo_code_applied');
            }
            if (!Schema::hasColumn('orders', 'membership_discount_amount')) {
                $table->decimal('membership_discount_amount', 15, 2)->default(0.00)->after('promo_discount_amount');
            }
            if (!Schema::hasColumn('orders', 'points_redemption_amount')) {
                $table->decimal('points_redemption_amount', 15, 2)->default(0.00)->after('membership_discount_amount');
            }
            if (!Schema::hasColumn('orders', 'is_full_points_redemption')) {
                $table->boolean('is_full_points_redemption')->default(false)->after('points_redemption_amount');
            }
            if (!Schema::hasColumn('orders', 'eligible_purchase_for_b2b')) {
                $table->decimal('eligible_purchase_for_b2b', 15, 2)->default(0.00)->after('is_full_points_redemption');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'promo_code_applied',
                'promo_discount_amount',
                'membership_discount_amount',
                'points_redemption_amount',
                'is_full_points_redemption',
                'eligible_purchase_for_b2b',
            ]);
        });
    }
};
