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
        Schema::table('inventory_vouchers', function (Blueprint $table) {
            $table->text('instructions')->nullable()->after('upload_vouchers');
            $table->string('email')->nullable()->after('instructions');
            $table->string('mobile_no')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_vouchers', function (Blueprint $table) {
            $table->dropColumn(['instructions', 'email', 'mobile_no']);
        });
    }
};
