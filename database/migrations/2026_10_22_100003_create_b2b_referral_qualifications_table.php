<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_referral_qualifications')) {
            Schema::create('b2b_referral_qualifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('referrer_id');
                $table->unsignedBigInteger('referred_id')->unique();
                $table->decimal('target_amount', 15, 2)->default(100000.00); // Rs. 100,000 threshold
                $table->timestamp('qualifying_start_date');
                $table->timestamp('qualifying_deadline'); // First 3 calendar months from registration
                $table->decimal('total_eligible_purchases', 15, 2)->default(0.00);
                $table->boolean('is_qualified')->default(false);
                $table->timestamp('qualified_at')->nullable();
                $table->string('status', 30)->default('in_progress'); // in_progress, qualified, expired, disqualified
                $table->timestamps();

                $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('referred_id')->references('id')->on('users')->onDelete('cascade');

                $table->index(['referrer_id', 'status']);
                $table->index('qualifying_deadline');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_referral_qualifications');
    }
};
