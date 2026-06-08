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
            $table->string('user_transaction_id')->nullable()->after('bank_details');
            $table->string('transaction_slip')->nullable()->after('user_transaction_id');
            $table->string('transaction_id')->nullable()->after('transaction_slip');
            $table->string('refund_receipt')->nullable()->after('transaction_id');
            $table->timestamp('processed_at')->nullable()->after('refund_receipt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['user_transaction_id', 'transaction_slip', 'transaction_id', 'refund_receipt', 'processed_at']);
        });
    }
};
