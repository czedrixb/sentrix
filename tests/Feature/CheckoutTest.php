<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    /**
     * The cart token the API handed back, replayed on subsequent requests the
     * way a browser replays the cart cookie.
     */
    private ?string $cartToken = null;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('customer', 'web');
        $this->branch = Branch::factory()->create(['is_pickup_location' => true]);
    }

    /**
     * Issue a JSON request carrying the current cart, remembering any cart the
     * response hands back.
     *
     * @param  array<string, mixed>  $payload
     */
    private function cartRequest(string $method, string $uri, array $payload = []): TestResponse
    {
        $headers = $this->cartToken !== null ? ['X-Cart-Token' => $this->cartToken] : [];

        $response = $this->json($method, $uri, $payload, $headers);

        $this->cartToken = $response->json('data.token') ?? $this->cartToken;

        return $response;
    }

    private function stocked(float $price, int $quantity = 10): Product
    {
        $product = Product::factory()->create(['price' => $price]);
        $product->branches()->syncWithoutDetaching([$this->branch->id => ['quantity' => $quantity]]);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_first_name' => 'Maria',
            'customer_last_name' => 'Santos',
            'customer_email' => 'maria@example.test',
            'customer_phone' => '9171234567',
            'fulfillment_type' => 'pickup',
            'scheduled_for' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
        ], $overrides);
    }

    private function addToCart(Product $product, int $quantity = 1): void
    {
        $this->cartRequest('POST', '/api/v1/cart/items', [
            'product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'quantity' => $quantity,
        ])->assertOk();
    }

    public function test_a_guest_can_place_an_order(): void
    {
        $product = $this->stocked(1500.00);
        $this->addToCart($product, 2);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())
            ->assertCreated()
            ->assertJsonPath('data.totals.grand_total', '3000.00')
            ->assertJsonPath('data.payment_status', PaymentStatus::Pending->value);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '1500.00',
            'line_total' => '3000.00',
        ]);
    }

    public function test_a_client_supplied_total_is_ignored(): void
    {
        // The previous system charged whatever the browser posted in a hidden input.
        $product = $this->stocked(1500.00);
        $this->addToCart($product, 2);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload([
            'total' => '1.00',
            'grand_total' => '1.00',
            'subtotal' => '1.00',
            'discount_total' => '2999.00',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.totals.grand_total', '3000.00')
            ->assertJsonPath('data.totals.discount_total', '0.00');
    }

    public function test_stock_is_decremented_once_the_order_is_placed(): void
    {
        $product = $this->stocked(500.00, 10);
        $this->addToCart($product, 3);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())->assertCreated();

        $this->assertSame(7, $this->stockFor($product));
    }

    public function test_ordering_more_than_available_stock_is_rejected(): void
    {
        $product = $this->stocked(500.00, 2);

        $this->cartRequest('POST', '/api/v1/cart/items', [
            'product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 5,
        ])->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_stock_is_not_partially_decremented_when_checkout_fails(): void
    {
        $ok = $this->stocked(500.00, 10);
        $short = $this->stocked(500.00, 10);

        $this->addToCart($ok, 2);
        $this->addToCart($short, 2);

        // Drain the second product behind the cart's back.
        DB::table('branch_product')
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $short->id)
            ->update(['quantity' => 1]);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())->assertStatus(422);

        // The first product must be untouched: the whole thing rolls back.
        $this->assertSame(10, $this->stockFor($ok));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_items_from_a_second_branch_are_rejected(): void
    {
        $other = Branch::factory()->create();
        $product = $this->stocked(500.00);

        $second = Product::factory()->create(['price' => 100]);
        $second->branches()->syncWithoutDetaching([$other->id => ['quantity' => 10]]);

        $this->addToCart($product, 1);

        $this->cartRequest('POST', '/api/v1/cart/items', [
            'product_id' => $second->id,
            'branch_id' => $other->id,
            'quantity' => 1,
        ])->assertStatus(422);
    }

    public function test_delivery_is_refused_when_nothing_in_the_cart_requires_it(): void
    {
        $this->addToCart($this->stocked(500.00), 1);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload([
            'fulfillment_type' => 'delivery',
            'delivery_address' => '123 Somewhere St',
        ]))->assertStatus(422);
    }

    public function test_a_voucher_discount_is_recomputed_and_recorded(): void
    {
        $voucher = Voucher::factory()->percentage(10)->create(['code' => 'TEN']);
        $this->addToCart($this->stocked(1000.00), 2);

        $this->cartRequest('POST', '/api/v1/cart/voucher', ['code' => 'TEN'])
            ->assertOk()
            ->assertJsonPath('data.totals.discount_total', '200.00');

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())
            ->assertCreated()
            ->assertJsonPath('data.totals.grand_total', '1800.00');

        $this->assertDatabaseHas('voucher_redemptions', ['amount' => '200.00']);
        $this->assertSame(1, (int) $voucher->fresh()->times_used);
    }

    public function test_an_order_placed_while_signed_in_is_linked_to_the_account(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user);
        $this->addToCart($this->stocked(750.00), 1);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())->assertCreated();

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }

    public function test_checkout_requires_a_future_schedule(): void
    {
        $this->addToCart($this->stocked(500.00), 1);

        $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload([
            'scheduled_for' => now()->subDay()->toDateTimeString(),
        ]))->assertStatus(422)->assertJsonValidationErrors('scheduled_for');
    }

    public function test_order_numbers_are_unique(): void
    {
        $product = $this->stocked(100.00, 50);
        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $this->addToCart($product, 1);

            $numbers[] = $this->cartRequest('POST', '/api/v1/orders', $this->checkoutPayload())
                ->assertCreated()
                ->json('data.order_number');
        }

        $this->assertCount(3, array_unique($numbers));
    }

    private function stockFor(Product $product): int
    {
        return (int) DB::table('branch_product')
            ->where('branch_id', $this->branch->id)
            ->where('product_id', $product->id)
            ->value('quantity');
    }
}
