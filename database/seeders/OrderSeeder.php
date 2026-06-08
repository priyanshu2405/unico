<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'order_id' => 'ORD-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'voucher_type' => $i % 2 == 0 ? 'Exam Voucher' : 'Discount Coupon',
                'amount' => rand(1000, 10000),
                'status' => 'completed',
                'payment_method' => 'Bank Transfer',
                'bank_name' => $i % 3 == 0 ? 'HBL' : 'Meezan Bank',
                'client_name' => 'Client ' . $i,
                'client_email' => 'client' . $i . '@example.com',
                'points_earned' => rand(10, 100),
                'points_redeemed' => 0,
                'bonus_amount' => rand(0, 500),
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
