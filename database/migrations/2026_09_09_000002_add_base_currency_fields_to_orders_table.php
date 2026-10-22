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
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('orders', 'exchange_rate_applied')) {
                $table->decimal('exchange_rate_applied', 14, 6)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('orders', 'amount_in_base_currency')) {
                $table->decimal('amount_in_base_currency', 15, 2)->nullable()->after('exchange_rate_applied');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('orders', 'currency')) {
                $columnsToDrop[] = 'currency';
            }
            if (Schema::hasColumn('orders', 'exchange_rate_applied')) {
                $columnsToDrop[] = 'exchange_rate_applied';
            }
            if (Schema::hasColumn('orders', 'amount_in_base_currency')) {
                $columnsToDrop[] = 'amount_in_base_currency';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
