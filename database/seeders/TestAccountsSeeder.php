<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('Password@123');

        $accounts = [
            [
                'email' => 'admin@unicou.nl',
                'name' => 'System Administrator',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'account_type' => 'admin',
                'user_id' => 'ADM00001',
                'phone' => '+1234567890',
                'country' => 'United States',
                'country_iso' => 'US',
            ],
            [
                'email' => 'manager@unicou.nl',
                'name' => 'Regional Manager',
                'first_name' => 'Regional',
                'last_name' => 'Manager',
                'account_type' => 'manager',
                'user_id' => 'MGR00001',
                'phone' => '+1234567891',
                'country' => 'United Kingdom',
                'country_iso' => 'GB',
            ],
            [
                'email' => 'reseller@unicou.nl',
                'name' => 'Reseller Agent',
                'first_name' => 'Prime',
                'last_name' => 'Reseller',
                'account_type' => 'reseller_agent',
                'user_id' => 'RSL00001',
                'phone' => '+1234567892',
                'country' => 'India',
                'country_iso' => 'IN',
                'business_name' => 'Prime Resellers Ltd',
                'business_type' => 'Training Institute',
            ],
            [
                'email' => 'agent@unicou.nl',
                'name' => 'Standard Agent',
                'first_name' => 'Direct',
                'last_name' => 'Agent',
                'account_type' => 'agent',
                'user_id' => 'AGT00001',
                'phone' => '+1234567893',
                'country' => 'Pakistan',
                'country_iso' => 'PK',
                'business_name' => 'Direct Education Consultants',
                'business_type' => 'Education Consultant',
            ],
            [
                'email' => 'student@unicou.nl',
                'name' => 'Test Student',
                'first_name' => 'John',
                'last_name' => 'Student',
                'account_type' => 'student',
                'user_id' => 'STU00001',
                'phone' => '+1234567894',
                'country' => 'Nepal',
                'country_iso' => 'NP',
                'exam_purpose' => 'PTE Academic Voucher',
            ],
            [
                'email' => 'support@unicou.nl',
                'name' => 'Support Specialist',
                'first_name' => 'Helpdesk',
                'last_name' => 'Support',
                'account_type' => 'support_team',
                'user_id' => 'SUP00001',
                'phone' => '+1234567895',
                'country' => 'United States',
                'country_iso' => 'US',
            ],
        ];

        foreach ($accounts as $acc) {
            $user = User::where('email', $acc['email'])->first();

            if (!$user) {
                $user = new User();
                $user->email = $acc['email'];
                $user->user_id = $acc['user_id'];
            }

            $user->name = $acc['name'];
            $user->first_name = $acc['first_name'];
            $user->last_name = $acc['last_name'];
            $user->account_type = $acc['account_type'];
            $user->password = $password;
            $user->phone = $acc['phone'];
            $user->country = $acc['country'];
            $user->country_iso = $acc['country_iso'];
            $user->business_name = $acc['business_name'] ?? null;
            $user->business_type = $acc['business_type'] ?? null;
            $user->exam_purpose = $acc['exam_purpose'] ?? null;
            $user->email_verified_at = now();
            $user->profile_verification_status = 'verified';
            $user->is_active = true;
            $user->save();

            echo "Created/Updated account for role [{$acc['account_type']}]: {$acc['email']}\n";
        }
    }
}
