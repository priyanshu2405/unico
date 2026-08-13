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
            $columns = [
                'sub_agent_id' => ['type' => 'unsignedBigInteger', 'nullable' => true],
                'first_name' => ['type' => 'string', 'nullable' => true],
                'last_name' => ['type' => 'string', 'nullable' => true],
                'company_name' => ['type' => 'string', 'nullable' => true],
                'phone' => ['type' => 'string', 'nullable' => true],
                'primary_contact' => ['type' => 'string', 'nullable' => true],
                'whatsapp_number' => ['type' => 'string', 'nullable' => true],
                'address' => ['type' => 'string', 'nullable' => true],
                'latitude' => ['type' => 'string', 'nullable' => true],
                'longitude' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'state' => ['type' => 'string', 'nullable' => true],
                'post_code' => ['type' => 'string', 'nullable' => true],
                'dob' => ['type' => 'string', 'nullable' => true],
                'id_type' => ['type' => 'string', 'nullable' => true],
                'id_number' => ['type' => 'string', 'nullable' => true],
                'aadhaar_number' => ['type' => 'string', 'nullable' => true],
                'aadhaar_document_path' => ['type' => 'string', 'nullable' => true],
                'verification_documents' => ['type' => 'json', 'nullable' => true],
                'verification_notes' => ['type' => 'text', 'nullable' => true],
                'verified_at' => ['type' => 'timestamp', 'nullable' => true],
                'wallet_balance' => ['type' => 'decimal', 'default' => 0.00, 'precision' => 15, 'scale' => 2],
                'profile_photo' => ['type' => 'string', 'nullable' => true],
                'verified_by' => ['type' => 'unsignedBigInteger', 'nullable' => true],
                'profile_updated_at' => ['type' => 'timestamp', 'nullable' => true],
                'pending_profile_data' => ['type' => 'json', 'nullable' => true],
                'country_code' => ['type' => 'string', 'nullable' => true],
                'country_iso' => ['type' => 'string', 'nullable' => true],
                'is_active' => ['type' => 'boolean', 'default' => true],
                'country' => ['type' => 'string', 'nullable' => true],
                'can_freeze_user' => ['type' => 'boolean', 'default' => false],
                'can_reset_password' => ['type' => 'boolean', 'default' => false],
                'can_create_user' => ['type' => 'boolean', 'default' => false],
                'can_edit_user' => ['type' => 'boolean', 'default' => false],
                'can_approve_user' => ['type' => 'boolean', 'default' => false],
                'can_view_users' => ['type' => 'boolean', 'default' => false],
                'can_impersonate_user' => ['type' => 'boolean', 'default' => false],
                'shufti_status' => ['type' => 'string', 'nullable' => true],
                'shufti_error' => ['type' => 'string', 'nullable' => true],
                'category' => ['type' => 'string', 'nullable' => true],
                'voucher_limit' => ['type' => 'integer', 'nullable' => true],
                'can_view_user_email_name' => ['type' => 'boolean', 'default' => false],
                'rating' => ['type' => 'decimal', 'nullable' => true, 'precision' => 3, 'scale' => 2],
                'has_job_permission' => ['type' => 'boolean', 'default' => false],
                'can_view_voucher_code' => ['type' => 'boolean', 'default' => false],
                'can_view_orders' => ['type' => 'boolean', 'default' => false],
                'can_view_sales_report' => ['type' => 'boolean', 'default' => false],
                'can_view_disputes' => ['type' => 'boolean', 'default' => false],
                'stripe_customer_id' => ['type' => 'string', 'nullable' => true],
                'withdrawable_balance' => ['type' => 'decimal', 'default' => 0.00, 'precision' => 15, 'scale' => 2]
            ];

            foreach ($columns as $name => $meta) {
                if (!Schema::hasColumn('users', $name)) {
                    $type = $meta['type'];
                    if ($type === 'decimal') {
                        $column = $table->decimal($name, $meta['precision'], $meta['scale']);
                    } else {
                        $column = $table->{$type}($name);
                    }
                    
                    if (isset($meta['nullable']) && $meta['nullable']) {
                        $column->nullable();
                    }
                    if (isset($meta['default'])) {
                        $column->default($meta['default']);
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse nothing to protect existing columns on local MySQL database.
    }
};
