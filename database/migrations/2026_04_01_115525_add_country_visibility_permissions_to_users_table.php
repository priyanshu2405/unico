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
            if (!Schema::hasColumn('users', 'can_view_all_countries')) {
                $table->boolean('can_view_all_countries')->default(true)->after('can_view_reports_page');
            }
            if (!Schema::hasColumn('users', 'permitted_countries')) {
                $table->json('permitted_countries')->nullable()->after('can_view_all_countries');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_view_all_countries', 'permitted_countries']);
        });
    }
};
