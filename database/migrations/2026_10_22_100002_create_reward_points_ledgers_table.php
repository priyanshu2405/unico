<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reward_points_ledgers')) {
            Schema::create('reward_points_ledgers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->integer('points'); // Positive for earned, negative for redeemed/reversed
                $table->integer('points_remaining')->default(0); // Tracks remaining unspent points for FIFO redemption
                $table->string('point_type', 50); // b2b_referral, student_referral, student_purchase_bonus, agent_monthly_bonus, agent_quarterly_bonus, agent_yearly_bonus, redemption, reversal, negative_offset
                $table->string('status', 30)->default('pending'); // pending, available, redeemed, cancelled, reversed, expired
                $table->timestamp('available_at')->nullable(); // Release date
                $table->timestamp('expires_at')->nullable(); // Expiry date
                $table->boolean('expiry_notified_30d')->default(false);
                $table->boolean('expiry_notified_7d')->default(false);
                $table->string('description', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

                $table->index(['user_id', 'status']);
                $table->index(['user_id', 'point_type']);
                $table->index('available_at');
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_points_ledgers');
    }
};
