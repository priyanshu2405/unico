<?php

namespace Tests\Feature;

use App\Models\B2bReferralQualification;
use App\Models\InventoryVoucher;
use App\Models\Membership;
use App\Models\Order;
use App\Models\RewardPointsLedger;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PointsService;
use App\Services\ReferralService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAndPointsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        if (!isset($attributes['user_id'])) {
            $attributes['user_id'] = 'UNIN' . strtoupper(bin2hex(random_bytes(4)));
        }
        return User::factory()->create($attributes);
    }

    /**
     * Test referral binding and anti-fraud self-referral checks.
     */
    public function test_user_registration_generates_referral_code_and_prevents_self_referral(): void
    {
        $referrer = $this->createUser([
            'account_type' => 'agent',
        ]);

        $code = ReferralService::ensureUserReferralCode($referrer);
        $this->assertNotEmpty($code);
        $this->assertEquals($code, $referrer->fresh()->referral_code);

        // Bind referred user
        $newUser = $this->createUser(['account_type' => 'agent']);
        $result = ReferralService::bindReferral($newUser, $code);
        $this->assertTrue($result['success']);
        $newUser->refresh();
        $this->assertEquals($referrer->id, $newUser->referred_by);

        // Anti-fraud: cannot refer oneself
        $ownCode = ReferralService::ensureUserReferralCode($newUser);
        $selfResult = ReferralService::bindReferral($newUser, $ownCode);
        $this->assertFalse($selfResult['success']);
    }

    /**
     * Test B2B Referral 3-calendar-month qualification and Rs. 100,000 threshold.
     */
    public function test_b2b_referral_qualification_threshold_and_points_accrual(): void
    {
        $agentReferrer = $this->createUser([
            'account_type' => 'agent',
        ]);
        $code = ReferralService::ensureUserReferralCode($agentReferrer);

        $referredAgent = $this->createUser([
            'account_type' => 'agent',
            'referred_by' => $agentReferrer->id,
            'referral_registered_at' => Carbon::now('Europe/London'),
        ]);

        // Place Order 1: Rs. 60,000 (< 100,000 threshold)
        $order1 = Order::create([
            'order_id' => 'ORD-B2B-01',
            'user_id' => $referredAgent->id,
            'voucher_type' => 'PTE',
            'voucher_id' => 'VCH-001',
            'quantity' => 1,
            'amount' => 60000,
            'currency' => 'PKR',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'voucher_status' => 'delivered',
            'created_at' => now(),
        ]);

        ReferralService::recordOrderDelivered($order1);

        $qualification = B2bReferralQualification::where('referred_id', $referredAgent->id)->first();
        $this->assertNotNull($qualification);
        $this->assertEquals(60000, (float) $qualification->total_eligible_purchases);
        $this->assertFalse((bool) $qualification->is_qualified);
        $this->assertEquals('in_progress', $qualification->status);

        // Check referrer ledger has pending points (1% of 60,000 = 600 points)
        $ledger1 = RewardPointsLedger::where('user_id', $agentReferrer->id)
            ->where('order_id', $order1->id)
            ->first();
        $this->assertNotNull($ledger1);
        $this->assertEquals(600, (float) $ledger1->points);
        $this->assertEquals('pending', $ledger1->status);

        // Place Order 2: Rs. 50,000 (Cumulative = 110,000 >= 100,000 target reached!)
        $order2 = Order::create([
            'order_id' => 'ORD-B2B-02',
            'user_id' => $referredAgent->id,
            'voucher_type' => 'PTE',
            'voucher_id' => 'VCH-002',
            'quantity' => 1,
            'amount' => 50000,
            'currency' => 'PKR',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'voucher_status' => 'delivered',
            'created_at' => now(),
        ]);

        ReferralService::recordOrderDelivered($order2);

        $qualification->refresh();
        $this->assertEquals(110000, (float) $qualification->total_eligible_purchases);
        $this->assertTrue((bool) $qualification->is_qualified);
        $this->assertEquals('qualified', $qualification->status);
        $this->assertNotNull($qualification->qualified_at);

        // Prior pending points should now have is_qualified marked in metadata
        $ledger1->refresh();
        $this->assertTrue($ledger1->metadata['is_qualified'] ?? false);
    }

    /**
     * Test Points balance calculation, minimum 100 redemption, and max 10% subtotal limit.
     */
    public function test_points_balance_and_redemption_rules(): void
    {
        $user = $this->createUser(['account_type' => 'agent']);

        // Create a voucher with Rs. 2,000 price
        $voucher = InventoryVoucher::create([
            'sku_id' => 'VCH-RED-01',
            'agent_sale_price' => 2000,
            'purchase_value_per_unit' => 1900,
        ]);

        // Grant 500 available points expiring in 30 days
        RewardPointsLedger::create([
            'user_id' => $user->id,
            'points' => 500,
            'points_remaining' => 500,
            'point_type' => 'b2b_referral',
            'status' => 'available',
            'available_at' => now()->subDay(),
            'expires_at' => now()->addDays(30),
            'description' => 'B2B referral reward',
        ]);

        $balance = PointsService::getUserPointsBalance($user);
        $this->assertEquals(500, $balance['net_available_points']);

        // Subtotal = PKR 2,000. 10% max adjustment = 200 points.
        // Attempting to redeem 250 points should be capped at 200.
        $calc1 = MembershipService::calculateOrderPricing($user, $voucher, 1, null, 250);
        $this->assertEquals(200, $calc1['points_redemption_amount']);
        $this->assertEquals(1800, $calc1['total_payable']);
        $this->assertTrue($calc1['is_full_points_redemption']); // Hit exactly 10%

        // Attempting to redeem 50 points (less than min 100) should be rejected
        $calc2 = MembershipService::calculateOrderPricing($user, $voucher, 1, null, 50);
        $this->assertEquals(0, $calc2['points_redemption_amount']);
        $this->assertEquals(2000, $calc2['total_payable']);

        // FIFO Redemption execution: Redeem 150 points
        $order = Order::create([
            'order_id' => 'ORD-RED-01',
            'user_id' => $user->id,
            'voucher_type' => 'PTE',
            'voucher_id' => 'VCH-RED-01',
            'quantity' => 1,
            'amount' => 1850,
            'currency' => 'PKR',
            'status' => 'pending',
            'points_redemption_amount' => 150,
        ]);

        PointsService::redeemPoints($user, 150, $order);

        $newBalance = PointsService::getUserPointsBalance($user);
        $this->assertEquals(350, $newBalance['net_available_points']);
        $this->assertEquals(150, $newBalance['redeemed_points']);
    }

    /**
     * Test Membership provides 1% immediate discount and expires at month end UK time.
     */
    public function test_membership_activation_and_discount(): void
    {
        $user = $this->createUser([
            'account_type' => 'agent',
            'wallet_balance' => 5000, // USD
        ]);

        $voucher = InventoryVoucher::create([
            'sku_id' => 'VCH-MEM-01',
            'agent_sale_price' => 100000,
            'purchase_value_per_unit' => 95000,
        ]);

        // Activate monthly membership
        $result = MembershipService::activateMembership($user, 'monthly', 'wallet');
        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Membership::class, $result['membership']);
        $this->assertTrue((bool) $user->fresh()->has_active_membership);
        $this->assertNotEmpty($user->fresh()->membership_promo_code);

        // Check UK month end expiry
        $expectedExpiry = Carbon::now('Europe/London')->endOfMonth();
        $this->assertEquals($expectedExpiry->format('Y-m-d H:i'), Carbon::parse($result['membership']->expires_at)->timezone('Europe/London')->format('Y-m-d H:i'));

        // Check 1% discount applied automatically on order subtotal Rs. 100,000 -> Rs. 1,000 discount
        $calc = MembershipService::calculateOrderPricing($user->fresh(), $voucher, 1, null, 0);
        $this->assertEquals(1000, $calc['membership_discount']);
        $this->assertEquals(99000, $calc['total_payable']);
    }

    /**
     * Test Order Refund reverses referral points and records negative offset for spent points.
     */
    public function test_refund_reverses_points_and_offsets_spent_balance(): void
    {
        $agent = $this->createUser(['account_type' => 'agent']);

        $order = Order::create([
            'order_id' => 'ORD-REF-01',
            'user_id' => $agent->id,
            'voucher_type' => 'PTE',
            'voucher_id' => 'VCH-004',
            'quantity' => 1,
            'amount' => 100000,
            'currency' => 'PKR',
            'status' => 'delivered',
            'payment_status' => 'paid',
            'voucher_status' => 'delivered',
            'created_at' => now(),
        ]);

        // Award 1,000 points that were already spent
        RewardPointsLedger::create([
            'user_id' => $agent->id,
            'order_id' => $order->id,
            'points' => 1000,
            'points_remaining' => 0, // points were already spent!
            'point_type' => 'b2b_referral',
            'status' => 'available',
            'available_at' => now()->subDays(5),
            'expires_at' => now()->addDays(90),
            'description' => 'Earned points',
        ]);

        // Handle refund of the order
        ReferralService::handleOrderRefund($order, 100000);

        // Check negative balance offset was recorded
        $negativeLedger = RewardPointsLedger::where('user_id', $agent->id)
            ->where('point_type', 'negative_offset')
            ->first();

        $this->assertNotNull($negativeLedger);
        $this->assertEquals(-1000, (float) $negativeLedger->points);

        $balance = PointsService::getUserPointsBalance($agent);
        $this->assertEquals(1000, $balance['negative_offset']);
        $this->assertEquals(0, $balance['net_available_points']);
    }
}
