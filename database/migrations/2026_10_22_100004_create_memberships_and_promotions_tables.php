<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('memberships')) {
            Schema::create('memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('plan_type', 30); // monthly, yearly
                $table->decimal('fee_paid', 15, 2);
                $table->string('currency', 10)->default('PKR');
                $table->decimal('discount_percent', 5, 2)->default(1.00); // Extra 1% discount
                $table->string('promo_code', 50)->unique();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('status', 30)->default('active'); // active, expired, cancelled
                $table->boolean('auto_renew')->default(false);
                $table->decimal('total_discount_saved', 15, 2)->default(0.00);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'status']);
                $table->index('expires_at');
            });
        }

        if (!Schema::hasTable('promotional_codes')) {
            Schema::create('promotional_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('title', 255);
                $table->string('discount_type', 30); // percentage, fixed_amount, bonus_points
                $table->decimal('discount_value', 15, 2);
                $table->string('currency', 10)->default('PKR');
                $table->decimal('min_order_amount', 15, 2)->default(0.00);
                $table->decimal('max_discount', 15, 2)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->integer('usage_limit_per_user')->default(1);
                $table->integer('total_usage_limit')->nullable();
                $table->integer('times_used')->default(0);
                $table->json('eligible_roles')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['code', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_codes');
        Schema::dropIfExists('memberships');
    }
};
