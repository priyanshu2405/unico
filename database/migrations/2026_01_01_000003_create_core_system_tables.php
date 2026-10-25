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
        // 1. System Settings
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('settings_key')->unique();
                $table->text('settings_value')->nullable();
            });

            // Seed default settings
            $defaultSettings = [
                ['settings_key' => 'maintenance_mode', 'settings_value' => 'off'],
                ['settings_key' => 'registration_enabled', 'settings_value' => 'on'],
                ['settings_key' => 'min_topup', 'settings_value' => '50'],
                ['settings_key' => 'daily_order_limit', 'settings_value' => '100'],
                ['settings_key' => 'announcement', 'settings_value' => 'Welcome to UniCou Voucher System!'],
                ['settings_key' => 'emergency_freeze', 'settings_value' => 'off'],
                ['settings_key' => 'stop_system_sales', 'settings_value' => 'off'],
                ['settings_key' => 'stop_country_sales', 'settings_value' => '[]'],
                ['settings_key' => 'stop_voucher_sales', 'settings_value' => '[]'],
                ['settings_key' => 'stop_all_sales', 'settings_value' => 'off'],
                ['settings_key' => 'stopped_brands', 'settings_value' => '[]'],
                ['settings_key' => 'stopped_countries', 'settings_value' => '[]'],
            ];

            foreach ($defaultSettings as $setting) {
                DB::table('system_settings')->insertOrIgnore($setting);
            }
        }

        // 2. Audit Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 3. Bank Accounts
        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('bank_name', 100);
                $table->string('account_holder_name', 100);
                $table->longText('account_number');
                $table->string('ifsc_code', 50);
                $table->string('account_type')->nullable();
                $table->decimal('balance', 15, 2)->default(0.00);
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }

        // 4. Job Vacancies
        if (!Schema::hasTable('job_vacancies')) {
            Schema::create('job_vacancies', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->string('category');
                $table->string('country')->nullable();
                $table->string('status')->default('open');
                $table->timestamps();
            });
        }

        // 5. Job Applications
        if (!Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vacancy_id')->nullable();
                $table->string('name')->nullable();
                $table->date('dob')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('whatsapp_number')->nullable();
                $table->string('social_link')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('post_code')->nullable();
                $table->string('reference_name')->nullable();
                $table->string('organization_name')->nullable();
                $table->string('reference_email')->nullable();
                $table->string('reference_phone')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->string('designation')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_country')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('id_document')->nullable();
                $table->string('photograph')->nullable();
                $table->string('reference_letter')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // 6. Pricing Rules
        if (!Schema::hasTable('pricing_rules')) {
            Schema::create('pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_id', 50)->nullable();
                $table->string('country_code', 5)->default('ALL');
                $table->decimal('base_price', 15, 2)->nullable();
                $table->decimal('discount_price', 15, 2)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 7. Legacy Vouchers
        if (!Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_id', 50)->unique();
                $table->string('name');
                $table->string('category', 100)->default('University');
                $table->decimal('price', 10, 2)->default(0.00);
                $table->integer('stock')->default(0);
                $table->text('description')->nullable();
                $table->string('status', 20)->default('active');
                $table->decimal('original_price', 10, 2)->nullable();
                $table->string('logo')->nullable();
                $table->integer('quarterly_points')->default(0);
                $table->integer('yearly_points')->default(0);
                $table->timestamps();
            });
        }

        // 8. Legacy Voucher Inventory
        if (!Schema::hasTable('voucher_inventory')) {
            Schema::create('voucher_inventory', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_id', 50)->nullable();
                $table->string('voucher_code')->nullable()->unique();
                $table->boolean('is_used')->default(false);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 9. Webhooks
        if (!Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url', 500);
                $table->text('secret');
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        // 10. Contact Us
        if (!Schema::hasTable('contact_us')) {
            Schema::create('contact_us', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('subject');
                $table->text('message');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // 11. Payments
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('payment_method')->default('bank_transfer');
                $table->string('provider')->default('wise');
                $table->string('transaction_id')->nullable();
                $table->string('reference_number')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->string('uid')->nullable();
                $table->string('stripe_session_id')->nullable();
                $table->string('stripe_payment_intent_id')->nullable();
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->string('currency', 10)->default('USD');
                $table->string('status', 30)->default('pending');
                $table->string('payment_type')->default('bank_transfer');
                $table->longText('metadata')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->string('customer_email')->nullable();
                $table->string('customer_name')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('voucher_inventory');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('contact_us');
        Schema::dropIfExists('payments');
    }
};
