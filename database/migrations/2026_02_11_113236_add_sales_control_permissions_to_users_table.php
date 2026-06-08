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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_stop_system_sales')->default(false)->after('can_impersonate_user');
            $table->boolean('can_stop_country_sales')->default(false)->after('can_stop_system_sales');
            $table->boolean('can_stop_voucher_sales')->default(false)->after('can_stop_country_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_stop_system_sales', 'can_stop_country_sales', 'can_stop_voucher_sales']);
        });
    }
};
