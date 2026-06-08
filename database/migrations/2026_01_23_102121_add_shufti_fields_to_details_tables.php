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
        Schema::table('agent_details', function (Blueprint $table) {
            $table->string('shufti_reference')->nullable()->after('business_logo');
        });

        Schema::table('student_details', function (Blueprint $table) {
            $table->string('shufti_reference')->nullable()->after('id_doc');
            $table->string('id_doc_final')->nullable()->after('shufti_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_details', function (Blueprint $table) {
            $table->dropColumn('shufti_reference');
        });

        Schema::table('student_details', function (Blueprint $table) {
            $table->dropColumn(['shufti_reference', 'id_doc_final']);
        });
    }
};
