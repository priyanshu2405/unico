<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('from_currency', 10);
                $table->string('to_currency', 10)->default('USD');
                $table->decimal('rate', 14, 6);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['from_currency', 'to_currency']);
            });

            // Seed initial common currencies with standard rates
            $now = now();
            DB::table('exchange_rates')->insert([
                ['from_currency' => 'USD', 'to_currency' => 'USD', 'rate' => 1.000000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'INR', 'to_currency' => 'USD', 'rate' => 0.012000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'PKR', 'to_currency' => 'USD', 'rate' => 0.003600, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'GBP', 'to_currency' => 'USD', 'rate' => 1.280000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'EUR', 'to_currency' => 'USD', 'rate' => 1.080000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'AED', 'to_currency' => 'USD', 'rate' => 0.272000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'TRY', 'to_currency' => 'USD', 'rate' => 0.030000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['from_currency' => 'NPR', 'to_currency' => 'USD', 'rate' => 0.007500, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
