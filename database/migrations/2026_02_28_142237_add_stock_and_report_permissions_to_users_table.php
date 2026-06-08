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
            $table->boolean('can_view_voucher_stock')->default(false)->after('can_view_disputes');
            $table->boolean('can_edit_voucher_stock')->default(false)->after('can_view_voucher_stock');
            $table->boolean('can_view_reports_page')->default(false)->after('can_edit_voucher_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_voucher_stock', 'can_edit_voucher_stock', 'can_view_reports_page']);
        });
    }
};
