<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\InventoryVoucher;
use App\Models\Order;
use App\Models\RewardPointsLedger;
use App\Models\WalletLedger;

class SeedVoucherTestDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_voucher_command_executes_successfully()
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('voucher:seed-test-data', ['--clean' => true]);
        dump(\Illuminate\Support\Facades\Artisan::output());
        $this->assertEquals(0, $exitCode);

        // 1. Check Global Vouchers
        $this->assertEquals(10, InventoryVoucher::count());
        $mainVoucher = InventoryVoucher::where('sku_id', 'GLOB-PTE-2026')->first();
        $this->assertNotNull($mainVoucher);
        $this->assertEquals(10, count($mainVoucher->upload_vouchers));
        $this->assertEquals(6, count($mainVoucher->delivered_vouchers));
        $this->assertEquals(4, $mainVoucher->quantity); // 10 - 6 = 4 remaining

        // 2. Check Test Users
        $agent1 = User::where('email', 'agent1@unicou.test')->first();
        $agent2 = User::where('email', 'agent2@unicou.test')->first();
        $student1 = User::where('email', 'student1@unicou.test')->first();
        $student2 = User::where('email', 'student2@unicou.test')->first();

        $this->assertNotNull($agent1);
        $this->assertNotNull($agent2);
        $this->assertNotNull($student1);
        $this->assertNotNull($student2);

        // 3. Check Wallet Credits (1 Lakh INR credited, then debited for purchases)
        // Check WalletLedger has topup credit of 1 Lakh INR (~1200 USD)
        $topupCredits = WalletLedger::where('source', 'wallet_topup')->get();
        $this->assertGreaterThanOrEqual(4, $topupCredits->count());
        foreach ($topupCredits as $credit) {
            $this->assertGreaterThan(1000, $credit->amount);
        }

        // 4. Check Orders: 4 orders totaling 6 vouchers
        $orders = Order::where('voucher_id', 'GLOB-PTE-2026')->get();
        $this->assertEquals(4, $orders->count());
        $this->assertEquals(6, $orders->sum('quantity'));
        foreach ($orders as $order) {
            $this->assertEquals('delivered', $order->status);
            $this->assertEquals('wallet', $order->payment_method);
            $this->assertNotEmpty($order->delivery_details);
        }

        // 5. Check Reward Points Ledger
        $availablePoints = RewardPointsLedger::where('status', 'available')->get();
        $pendingPoints = RewardPointsLedger::where('status', 'pending')->get();
        $heldPoints = RewardPointsLedger::where('status', 'held')->get();
        $redeemedPoints = RewardPointsLedger::where('status', 'redeemed')->get();

        $this->assertGreaterThan(0, $availablePoints->count());
        $this->assertGreaterThan(0, $pendingPoints->count());
        $this->assertGreaterThan(0, $heldPoints->count());
        $this->assertGreaterThan(0, $redeemedPoints->count());

        // Check types include bonus and referral
        $types = RewardPointsLedger::pluck('point_type')->unique()->toArray();
        $this->assertContains('agent_monthly_bonus', $types);
        $this->assertContains('student_purchase_bonus', $types);
        $this->assertContains('b2b_referral', $types);
        $this->assertContains('student_referral', $types);
    }
}
