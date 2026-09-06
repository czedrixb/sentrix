<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard's two data panels.
 *
 * Low stock and the revenue chart are their own endpoints so the panel can page
 * and search without refetching every counter on the screen. Both are branch
 * scoped for branch staff, the same as everything else in the admin.
 */
class AdminDashboardTest extends TestCase
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

    private function staff(string $role = 'admin', ?Branch $branch = null): User
    {
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Set a product's level at one branch.
     *
     * ProductObserver already gave the product a row at every branch, so this
     * updates rather than attaches.
     */
    private function stock(Product $product, Branch $branch, int $quantity, int $threshold = 5): void
    {
        $product->branches()->updateExistingPivot($branch->id, [
            'quantity' => $quantity,
            'low_stock_threshold' => $threshold,
        ]);
    }

    /**
     * A product nobody needs to worry about, anywhere.
     *
     * Those observer-created rows default to quantity 0 AND threshold 0, and
     * `0 <= 0` is low stock, so a product left untouched shows up at every
     * branch. Tests stock it healthy first, then push down the one branch under
     * examination.
     */
    private function wellStocked(?string $name = null, ?string $sku = null): Product
    {
        $product = Product::factory()->create(array_filter([
            'name' => $name,
            'sku' => $sku,
        ]));

        foreach ([$this->davao, $this->cebu] as $branch) {
            $this->stock($product, $branch, quantity: 99, threshold: 5);
        }

        return $product;
    }

    // --- Low stock -------------------------------------------------------

    public function test_low_stock_lists_only_products_at_or_below_their_threshold(): void
    {
        $low = $this->wellStocked('Scarce Toner');
        $this->wellStocked('Plentiful Paper');

        $this->stock($low, $this->davao, quantity: 2, threshold: 5);

        $response = $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/low-stock')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.product_name', 'Scarce Toner');
        $response->assertJsonPath('data.0.quantity', 2);
        $response->assertJsonPath('data.0.branch_name', 'Davao City');
    }

    public function test_low_stock_pages_five_rows_at_a_time_scarcest_first(): void
    {
        // Seven rows, so the default page size has to leave two behind.
        foreach (range(1, 7) as $index) {
            $product = $this->wellStocked("Product {$index}");
            $this->stock($product, $this->davao, quantity: $index, threshold: 10);
        }

        $first = $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/low-stock')
            ->assertOk();

        $first->assertJsonCount(5, 'data');
        $first->assertJsonPath('meta.total', 7);
        $first->assertJsonPath('meta.last_page', 2);
        $first->assertJsonPath('data.0.quantity', 1);

        $second = $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/low-stock?page=2')
            ->assertOk();

        $second->assertJsonCount(2, 'data');
        $second->assertJsonPath('data.0.quantity', 6);
    }

    public function test_low_stock_searches_product_name_sku_and_branch(): void
    {
        $toner = $this->wellStocked('Black Toner', 'TON-001');
        $drum = $this->wellStocked('Drum Unit', 'DRM-999');

        $this->stock($toner, $this->davao, quantity: 1);
        $this->stock($drum, $this->cebu, quantity: 1);

        $admin = $this->staff();

        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/low-stock?q=toner')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'TON-001');

        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/low-stock?q=DRM-999')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_name', 'Drum Unit');

        // Branch name is searchable too, which is the only way to narrow the
        // list by location when you are not scoped to a branch yourself.
        $this->actingAs($admin)->getJson('/api/v1/admin/dashboard/low-stock?q=Cebu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.branch_name', 'Cebu City');
    }

    public function test_low_stock_is_scoped_to_the_branch_a_staff_member_belongs_to(): void
    {
        $mine = $this->wellStocked('Davao Item');
        $theirs = $this->wellStocked('Cebu Item');

        $this->stock($mine, $this->davao, quantity: 1);
        $this->stock($theirs, $this->cebu, quantity: 1);

        $this->actingAs($this->staff('branch_manager', $this->davao))
            ->getJson('/api/v1/admin/dashboard/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_name', 'Davao Item');
    }

    public function test_low_stock_ignores_archived_products(): void
    {
        $archived = Product::factory()->archived()->create();
        $this->stock($archived, $this->davao, quantity: 0);

        $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/low-stock')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // --- Revenue series --------------------------------------------------

    public function test_revenue_defaults_to_twelve_weekly_buckets(): void
    {
        $response = $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/revenue')
            ->assertOk();

        $response->assertJsonPath('data.range', '12w');
        $response->assertJsonPath('data.unit', 'week');
        $response->assertJsonCount(12, 'data.points');
    }

    public function test_revenue_buckets_paid_orders_and_zero_fills_quiet_periods(): void
    {
        Order::factory()->paid()->create([
            'branch_id' => $this->davao->id,
            'grand_total' => 1000,
            'created_at' => now(),
        ]);

        // Unpaid revenue is not revenue.
        Order::factory()->create([
            'branch_id' => $this->davao->id,
            'grand_total' => 5000,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/revenue?range=30d')
            ->assertOk();

        $response->assertJsonPath('data.unit', 'day');
        $response->assertJsonCount(30, 'data.points');
        $response->assertJsonPath('data.total', '1000');

        // Today is the final bucket; every earlier one is present and empty.
        $points = $response->json('data.points');
        $this->assertSame('1000', $points[29]['value']);
        $this->assertSame('0', $points[0]['value']);
    }

    public function test_revenue_is_scoped_to_the_branch_a_staff_member_belongs_to(): void
    {
        Order::factory()->paid()->create([
            'branch_id' => $this->cebu->id,
            'grand_total' => 4200,
            'created_at' => now(),
        ]);

        $this->actingAs($this->staff('branch_manager', $this->davao))
            ->getJson('/api/v1/admin/dashboard/revenue?range=30d')
            ->assertOk()
            ->assertJsonPath('data.total', '0');
    }

    public function test_revenue_falls_back_to_the_default_range_for_an_unknown_one(): void
    {
        $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/revenue?range=all-time')
            ->assertOk()
            ->assertJsonPath('data.range', '12w')
            ->assertJsonCount(12, 'data.points');
    }

    public function test_revenue_supports_a_monthly_range(): void
    {
        $this->actingAs($this->staff())
            ->getJson('/api/v1/admin/dashboard/revenue?range=12m')
            ->assertOk()
            ->assertJsonPath('data.unit', 'month')
            ->assertJsonCount(12, 'data.points');
    }

    // --- Authorisation ---------------------------------------------------

    public function test_both_panels_require_the_dashboard_permission(): void
    {
        $customer = $this->staff('customer');

        $this->actingAs($customer)->getJson('/api/v1/admin/dashboard/low-stock')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/v1/admin/dashboard/revenue')->assertForbidden();
    }

    /**
     * Kept separate from the test above: actingAs persists for the remainder of
     * a test, so a guest assertion sharing that method would still be signed in.
     */
    public function test_both_panels_reject_guests(): void
    {
        $this->getJson('/api/v1/admin/dashboard/low-stock')->assertUnauthorized();
        $this->getJson('/api/v1/admin/dashboard/revenue')->assertUnauthorized();
    }
}
