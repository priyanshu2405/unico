<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 64)->nullable()->unique()->after('user_id');
            }
            if (!Schema::hasColumn('users', 'referred_by')) {
                $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
                $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'referral_registered_at')) {
                $table->timestamp('referral_registered_at')->nullable()->after('referred_by');
            }
            if (!Schema::hasColumn('users', 'has_active_membership')) {
                $table->boolean('has_active_membership')->default(false)->after('referral_registered_at');
            }
            if (!Schema::hasColumn('users', 'membership_type')) {
                $table->string('membership_type', 50)->nullable()->after('has_active_membership');
            }
            if (!Schema::hasColumn('users', 'membership_expires_at')) {
                $table->timestamp('membership_expires_at')->nullable()->after('membership_type');
            }
            if (!Schema::hasColumn('users', 'membership_promo_code')) {
                $table->string('membership_promo_code', 50)->nullable()->after('membership_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by')) {
                $table->dropForeign(['referred_by']);
            }
            $table->dropColumn([
                'referral_code',
                'referred_by',
                'referral_registered_at',
                'has_active_membership',
                'membership_type',
                'membership_expires_at',
                'membership_promo_code'
            ]);
        });
    }
};
