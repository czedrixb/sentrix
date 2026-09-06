<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\Pricing\CartTotals;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The discount rules the previous system computed inside a Blade template,
 * pinned down server-side. Each test here corresponds to a real defect in that
 * implementation.
 */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cartWith(array $lines, ?Voucher $voucher = null): Cart
    {
        $branch = Branch::factory()->create();
        $cart = Cart::factory()->create([
            'branch_id' => $branch->id,
            'voucher_id' => $voucher?->id,
        ]);

        foreach ($lines as [$price, $quantity]) {
            $product = Product::factory()->create(['price' => $price]);
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $price,
            ]);
        }

        return $cart->fresh(['items.product', 'voucher.products', 'branch']);
    }

    private function price(Cart $cart): CartTotals
    {
        return app(PricingService::class)->priceCart($cart);
    }

    public function test_subtotal_is_the_sum_of_catalogue_prices(): void
    {
        $totals = $this->price($this->cartWith([[1000.00, 2], [250.50, 1]]));

        $this->assertSame('2250.50', $totals->toDecimals()['subtotal']);
        $this->assertSame('2250.50', $totals->toDecimals()['grand_total']);
    }

    public function test_percentage_discount_is_applied_once_not_once_per_item(): void
    {
        // Four lines, 10% off a 4,000 subtotal is 400 - not 400 x 4.
        $voucher = Voucher::factory()->percentage(10)->create();
        $totals = $this->price($this->cartWith([[1000, 1], [1000, 1], [1000, 1], [1000, 1]], $voucher));

        $this->assertSame('400.00', $totals->toDecimals()['discount_total']);
        $this->assertSame('3600.00', $totals->toDecimals()['grand_total']);
    }

    public function test_fixed_discount_subtracts_a_flat_amount(): void
    {
        $voucher = Voucher::factory()->fixed(500)->create();
        $totals = $this->price($this->cartWith([[2000, 1]], $voucher));

        $this->assertSame('500.00', $totals->toDecimals()['discount_total']);
        $this->assertSame('1500.00', $totals->toDecimals()['grand_total']);
    }

    public function test_a_discount_can_never_exceed_the_cart_value(): void
    {
        $voucher = Voucher::factory()->fixed(5000)->create();
        $totals = $this->price($this->cartWith([[800, 1]], $voucher));

        $this->assertSame('800.00', $totals->toDecimals()['discount_total']);
        $this->assertSame('0.00', $totals->toDecimals()['grand_total']);
    }

    public function test_minimum_quantity_voucher_is_rejected_below_the_threshold(): void
    {
        $voucher = Voucher::factory()->percentageWithMinimumQuantity(15, 3)->create();
        $totals = $this->price($this->cartWith([[1000, 2]], $voucher));

        $this->assertSame('0.00', $totals->toDecimals()['discount_total']);
        $this->assertNotNull($totals->voucherRejectionReason);
    }

    public function test_minimum_quantity_voucher_applies_once_the_threshold_is_met(): void
    {
        $voucher = Voucher::factory()->percentageWithMinimumQuantity(15, 3)->create();
        $totals = $this->price($this->cartWith([[1000, 3]], $voucher));

        $this->assertSame('450.00', $totals->toDecimals()['discount_total']);
        $this->assertNull($totals->voucherRejectionReason);
    }

    public function test_product_scoped_voucher_only_discounts_its_own_products(): void
    {
        // The previous implementation compared an id to itself, so scoping
        // never actually restricted anything.
        $voucher = Voucher::factory()->percentage(50)->create();

        $cart = $this->cartWith([[1000, 1], [3000, 1]], $voucher);
        $eligible = $cart->items->first()->product;
        $voucher->products()->sync([$eligible->id]);

        $totals = $this->price($cart->fresh(['items.product', 'voucher.products', 'branch']));

        $this->assertSame('4000.00', $totals->toDecimals()['subtotal']);
        $this->assertSame('500.00', $totals->toDecimals()['discount_total']);
        $this->assertSame('3500.00', $totals->toDecimals()['grand_total']);
    }

    public function test_expired_voucher_is_rejected(): void
    {
        $voucher = Voucher::factory()->percentage(10)->expired()->create();
        $totals = $this->price($this->cartWith([[1000, 1]], $voucher));

        $this->assertSame('0.00', $totals->toDecimals()['discount_total']);
        $this->assertNull($totals->voucher);
    }

    public function test_voucher_that_matches_nothing_in_the_cart_is_rejected(): void
    {
        $voucher = Voucher::factory()->percentage(10)->create();
        $voucher->products()->sync([Product::factory()->create()->id]);

        $totals = $this->price($this->cartWith([[1000, 1]], $voucher));

        $this->assertSame('0.00', $totals->toDecimals()['discount_total']);
        $this->assertNotNull($totals->voucherRejectionReason);
    }

    public function test_repeated_percentages_do_not_drift(): void
    {
        // 33.33% of 0.10 twenty times: integer centavos must not accumulate error.
        $voucher = Voucher::factory()->percentage(33.33)->create();
        $totals = $this->price($this->cartWith([[0.10, 20]], $voucher));

        $this->assertSame('2.00', $totals->toDecimals()['subtotal']);
        $this->assertSame('0.67', $totals->toDecimals()['discount_total']);
    }
}
