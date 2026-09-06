<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorisation is by permission, and branch staff are scoped per row.
 *
 * The previous system scoped only the order index query; every single-order
 * action did a bare findOrFail, so any branch admin could read or mutate any
 * other branch's orders by guessing an id.
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $davao;

    private Branch $cebu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->davao = Branch::factory()->create(['name' => 'Davao City']);
        $this->cebu = Branch::factory()->create(['name' => 'Cebu City']);
    }

    private function staff(string $role, ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_a_branch_manager_cannot_read_another_branchs_order(): void
    {
        $manager = $this->staff('branch_manager', $this->davao);
        $foreign = Order::factory()->create(['branch_id' => $this->cebu->id]);

        $this->actingAs($manager)
            ->getJson('/api/v1/admin/orders/'.$foreign->order_number)
            ->assertForbidden();
    }

    public function test_a_branch_manager_cannot_mutate_another_branchs_order(): void
    {
        $manager = $this->staff('branch_manager', $this->davao);
        $foreign = Order::factory()->create(['branch_id' => $this->cebu->id]);

        $this->actingAs($manager)
            ->patchJson('/api/v1/admin/orders/'.$foreign->order_number.'/status', ['status' => 'completed'])
            ->assertForbidden();

        $this->assertSame('pending', $foreign->fresh()->status->value);
    }

    public function test_a_branch_manager_sees_only_their_own_branchs_orders(): void
    {
        $manager = $this->staff('branch_manager', $this->davao);

        Order::factory()->create(['branch_id' => $this->davao->id]);
        Order::factory()->count(2)->create(['branch_id' => $this->cebu->id]);

        $this->actingAs($manager)
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_branch_manager_can_act_on_their_own_branchs_order(): void
    {
        $manager = $this->staff('branch_manager', $this->davao);
        $own = Order::factory()->create(['branch_id' => $this->davao->id]);

        $this->actingAs($manager)
            ->patchJson('/api/v1/admin/orders/'.$own->order_number.'/status', ['status' => 'completed'])
            ->assertOk();

        $this->assertSame('completed', $own->fresh()->status->value);
    }

    public function test_an_unscoped_admin_sees_every_branch(): void
    {
        Order::factory()->create(['branch_id' => $this->davao->id]);
        Order::factory()->create(['branch_id' => $this->cebu->id]);

        $this->actingAs($this->staff('admin'))
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_role_without_the_permission_is_refused(): void
    {
        // HR has no product permissions at all.
        $this->actingAs($this->staff('hr'))
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    public function test_an_auditor_can_read_but_not_write(): void
    {
        $auditor = $this->staff('auditor');

        $this->actingAs($auditor)->getJson('/api/v1/admin/products')->assertOk();

        $this->actingAs($auditor)
            ->postJson('/api/v1/admin/products', ['sku' => 'X-1', 'name' => 'Nope', 'price' => 1])
            ->assertForbidden();
    }

    public function test_a_customer_cannot_reach_the_admin_api(): void
    {
        $customer = $this->staff('customer');

        $this->actingAs($customer)->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/v1/admin/orders')->assertForbidden();
    }

    public function test_the_admin_api_rejects_guests(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }

    public function test_branch_staff_see_their_own_and_general_inquiries_only(): void
    {
        $manager = $this->staff('branch_manager', $this->davao);

        Inquiry::factory()->create(['branch_id' => $this->davao->id]);
        Inquiry::factory()->general()->create();
        Inquiry::factory()->create(['branch_id' => $this->cebu->id]);

        $this->actingAs($manager)
            ->getJson('/api/v1/admin/inquiries')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_stock_can_be_adjusted_for_any_branch_including_a_new_one(): void
    {
        $inventory = $this->staff('inventory');
        $product = Product::factory()->create();

        // A branch created after the product exists is immediately usable.
        $newBranch = Branch::create(['name' => 'Bacolod City']);

        $this->actingAs($inventory)
            ->patchJson('/api/v1/admin/products/'.$product->slug.'/stock', [
                'branch_id' => $newBranch->id,
                'quantity' => 25,
                'low_stock_threshold' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $newBranch->id,
            'product_id' => $product->id,
            'quantity' => 25,
        ]);
    }
}
