<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\InventoryVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create users with valid unique user_ids to satisfy database constraints.
     */
    protected function createUser(array $attributes = []): User
    {
        if (!isset($attributes['user_id'])) {
            $attributes['user_id'] = 'UNIN' . strtoupper(bin2hex(random_bytes(4)));
        }
        return User::factory()->create($attributes);
    }

    /**
     * Test admin can update permissions of a support team user.
     */
    public function test_admin_can_update_permissions(): void
    {
        $admin = $this->createUser([
            'account_type' => 'admin',
        ]);

        $support = $this->createUser([
            'account_type' => 'support_team',
            'can_process_refunds' => false,
            'can_view_voucher_stock' => false,
            'can_edit_voucher_stock' => false,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('users.permissions.update', $support->id), [
                'can_process_refunds' => 'on',
                'can_view_voucher_stock' => 'on',
                'can_edit_voucher_stock' => 'on',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302); // Redirect back

        $support->refresh();
        $this->assertTrue($support->can_process_refunds);
        $this->assertTrue($support->can_view_voucher_stock);
        $this->assertTrue($support->can_edit_voucher_stock);
    }

    /**
     * Test non-admin cannot update permissions.
     */
    public function test_non_admin_cannot_update_permissions(): void
    {
        $manager = $this->createUser([
            'account_type' => 'manager',
        ]);

        $support = $this->createUser([
            'account_type' => 'support_team',
            'can_process_refunds' => false,
        ]);

        $response = $this->actingAs($manager)
            ->post(route('users.permissions.update', $support->id), [
                'can_process_refunds' => 'on',
            ]);

        $response->assertStatus(302);
        
        $support->refresh();
        $this->assertFalse($support->can_process_refunds);
    }

    /**
     * Test refunds permission enforcement.
     */
    public function test_refunds_permission_enforcement(): void
    {
        $supportWithPerm = $this->createUser([
            'account_type' => 'support_team',
            'can_process_refunds' => true,
        ]);

        $supportWithoutPerm = $this->createUser([
            'account_type' => 'support_team',
            'can_process_refunds' => false,
        ]);

        // Authorized
        $response = $this->actingAs($supportWithPerm)->get(route('refunds.index'));
        $response->assertStatus(200);

        // Unauthorized
        $response = $this->actingAs($supportWithoutPerm)->get(route('refunds.index'));
        $response->assertStatus(403);
    }

    /**
     * Test inventory permission enforcement.
     */
    public function test_inventory_permission_enforcement(): void
    {
        $supportWithView = $this->createUser([
            'account_type' => 'support_team',
            'can_view_voucher_stock' => true,
            'can_edit_voucher_stock' => false,
        ]);

        $supportWithEdit = $this->createUser([
            'account_type' => 'support_team',
            'can_view_voucher_stock' => true,
            'can_edit_voucher_stock' => true,
        ]);

        $supportWithoutPerm = $this->createUser([
            'account_type' => 'support_team',
            'can_view_voucher_stock' => false,
            'can_edit_voucher_stock' => false,
        ]);

        // Test view permission
        $response = $this->actingAs($supportWithView)->get(route('inventory.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($supportWithoutPerm)->get(route('inventory.index'));
        $response->assertStatus(403);

        // Test edit permission on store
        // If authorized but validation fails, we expect a redirect (302) instead of a 403.
        $response = $this->actingAs($supportWithView)->post(route('inventory.store'), []);
        $response->assertStatus(403);

        $response = $this->actingAs($supportWithEdit)->post(route('inventory.store'), []);
        $response->assertStatus(302); // Bypassed 403, redirected due to validation failure
    }

    /**
     * Test that non-admins cannot freeze/suspend/impersonate administrative accounts.
     */
    public function test_administrative_account_protection(): void
    {
        $supportWithFreeze = $this->createUser([
            'account_type' => 'support_team',
            'can_freeze_user' => true,
            'can_impersonate_user' => true,
        ]);

        $targetManager = $this->createUser([
            'account_type' => 'manager',
            'is_active' => true,
        ]);

        $targetUser = $this->createUser([
            'account_type' => 'user',
            'is_active' => true,
        ]);

        // Attempting to freeze a standard user (authorized)
        $response = $this->actingAs($supportWithFreeze)->postJson(route('users.toggle', $targetUser->id));
        $response->assertStatus(200); // toggleStatus returns JSON success
        $targetUser->refresh();
        $this->assertEquals(0, $targetUser->is_active);

        // Attempting to freeze a manager (unauthorized)
        $response = $this->actingAs($supportWithFreeze)->post(route('users.toggle', $targetManager->id));
        $response->assertStatus(403);
        $targetManager->refresh();
        $this->assertEquals(1, $targetManager->is_active);

        // Attempting to impersonate a manager (unauthorized)
        $response = $this->actingAs($supportWithFreeze)->post(route('users.impersonate', $targetManager->id));
        $response->assertStatus(302); // Redirects back with error message
        $response->assertSessionHas('error', 'You cannot impersonate administrative accounts.');
    }
}
