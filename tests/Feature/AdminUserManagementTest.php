<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff account management.
 *
 * `Model::shouldBeStrict()` is on outside production, and a paginated
 * collection trips its lazy-loading guard the moment a resource touches an
 * unloaded relation on more than one row -- a single `first()`-fetched model
 * does not. `UserResource` reads both `roles` and `permissions`, so the list
 * endpoint needs both eager loaded, not just the one relation a single-user
 * fetch happened to get away without.
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function staff(string $role = 'admin', ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_listing_several_staff_does_not_trip_the_lazy_loading_guard(): void
    {
        $this->staff('sales');
        $this->staff('inventory');
        $this->staff('accounts');

        $this->actingAs($this->staff('admin'))
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles', 'permissions', 'is_active']]]);
    }

    public function test_admin_can_create_a_staff_account(): void
    {
        $response = $this->actingAs($this->staff('admin'))
            ->postJson('/api/v1/admin/users', [
                'name' => 'New Hire',
                'email' => 'new.hire@sentrix.test',
                'password' => 'password123',
                'role' => 'sales',
            ])
            ->assertCreated();

        $response->assertJsonPath('data.roles', ['sales']);
        $this->assertDatabaseHas('users', ['email' => 'new.hire@sentrix.test']);
    }

    public function test_admin_can_update_a_staff_accounts_role_and_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->staff('sales');

        $this->actingAs($this->staff('admin'))
            ->patchJson('/api/v1/admin/users/'.$user->id, [
                'role' => 'branch_manager',
                'branch_id' => $branch->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.roles', ['branch_manager'])
            ->assertJsonPath('data.branch.id', $branch->id);
    }

    public function test_deactivating_a_staff_account_does_not_delete_it(): void
    {
        $user = $this->staff('sales');

        $this->actingAs($this->staff('admin'))
            ->deleteJson('/api/v1/admin/users/'.$user->id)
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_an_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)
            ->deleteJson('/api/v1/admin/users/'.$admin->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }
}
