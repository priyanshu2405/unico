<?php

namespace Tests\Feature;

use App\Models\RewardPointsLedger;
use App\Models\User;
use App\Services\PointsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPointsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        if (!isset($attributes['user_id'])) {
            $attributes['user_id'] = 'UNIN' . strtoupper(bin2hex(random_bytes(4)));
        }
        return User::factory()->create(array_merge([
            'profile_verification_status' => 'verified',
            'business_name' => 'Agent Business Ltd',
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Test admin can view all referral ledgers while regular agent only sees their own.
     */
    public function test_admin_can_view_all_referral_points_and_agent_only_sees_own(): void
    {
        $admin = $this->createUser(['account_type' => 'admin']);
        $agent1 = $this->createUser(['account_type' => 'agent']);
        $agent2 = $this->createUser(['account_type' => 'agent']);

        // Create ledger for agent 1
        RewardPointsLedger::create([
            'user_id' => $agent1->id,
            'points' => 500,
            'points_remaining' => 500,
            'point_type' => 'b2b_referral',
            'status' => 'pending',
            'available_at' => now()->addDays(14),
            'description' => 'Referral reward for Agent 1',
        ]);

        // Create ledger for agent 2
        RewardPointsLedger::create([
            'user_id' => $agent2->id,
            'points' => 300,
            'points_remaining' => 300,
            'point_type' => 'student_referral',
            'status' => 'pending',
            'available_at' => now()->addDays(7),
            'description' => 'Referral reward for Agent 2',
        ]);

        // Admin visiting /dashboard/referral sees both records
        $response = $this->actingAs($admin)->get(route('referral'));
        $response->assertStatus(200);
        $response->assertSee('Referral reward for Agent 1');
        $response->assertSee('Referral reward for Agent 2');

        // Agent 1 visiting /dashboard/referral only sees Agent 1's record
        $agentResponse = $this->actingAs($agent1)->get(route('referral'));
        $agentResponse->assertStatus(200);
        $agentResponse->assertSee('Referral reward for Agent 1');
        $agentResponse->assertDontSee('Referral reward for Agent 2');
    }

    /**
     * Test admin can hold and release points.
     */
    public function test_admin_can_hold_and_release_point_ledger(): void
    {
        $admin = $this->createUser(['account_type' => 'admin']);
        $agent = $this->createUser(['account_type' => 'agent']);

        $ledger = RewardPointsLedger::create([
            'user_id' => $agent->id,
            'points' => 1000,
            'points_remaining' => 1000,
            'point_type' => 'b2b_referral',
            'status' => 'pending',
            'available_at' => now()->addDays(14),
            'description' => 'Pending reward',
        ]);

        // Admin puts points on hold
        $holdResponse = $this->actingAs($admin)->post(route('points.status.update', $ledger->id), [
            'action' => 'hold',
            'notes' => 'Suspected self-referral ring investigation',
        ]);
        $holdResponse->assertSessionHas('success');
        $ledger->refresh();
        $this->assertEquals('held', $ledger->status);
        $this->assertEquals('Suspected self-referral ring investigation', $ledger->metadata['admin_notes']);

        // When points are held, they are not available for redemption
        $balance = PointsService::getUserPointsBalance($agent);
        $this->assertEquals(0, $balance['net_available_points']);

        // Admin approves and releases points immediately
        $releaseResponse = $this->actingAs($admin)->post(route('points.status.update', $ledger->id), [
            'action' => 'release',
            'notes' => 'Investigation cleared, releasing bonus',
        ]);
        $releaseResponse->assertSessionHas('success');
        $ledger->refresh();
        $this->assertEquals('available', $ledger->status);
        $this->assertTrue(now()->gte($ledger->available_at));

        // Now points are spendable
        $balanceAfter = PointsService::getUserPointsBalance($agent);
        $this->assertEquals(1000, $balanceAfter['net_available_points']);
    }

    /**
     * Test regular agent cannot modify point status.
     */
    public function test_non_admin_cannot_update_point_status(): void
    {
        $agent = $this->createUser(['account_type' => 'agent']);

        $ledger = RewardPointsLedger::create([
            'user_id' => $agent->id,
            'points' => 1000,
            'points_remaining' => 1000,
            'point_type' => 'b2b_referral',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($agent)->post(route('points.status.update', $ledger->id), [
            'action' => 'release',
        ]);
        $response->assertStatus(403);
    }
}
