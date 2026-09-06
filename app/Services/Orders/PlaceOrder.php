<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Pricing\CartTotals;
use App\Services\Pricing\PricingService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Turns a cart into an order.
 *
 * Everything happens inside one transaction with the stock rows locked, so a
 * failure part-way cannot leave stock decremented for some lines and not
 * others, which the previous implementation did on every out-of-stock error.
 */
class PlaceOrder
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly CartService $carts,
        private readonly OrderNumberGenerator $orderNumbers,
    ) {}

    /**
     * @param  array{customer_first_name: string, customer_last_name: string, customer_email: string, customer_phone: string, delivery_address?: ?string, notes?: ?string, scheduled_for?: ?string, fulfillment_type: string}  $details
     *
     * @throws CheckoutException
     */
    public function handle(Cart $cart, array $details, ?User $user = null): Order
    {
        $cart->loadMissing(['items.product', 'branch', 'voucher.products']);

        $this->assertCartIsOrderable($cart);

        $fulfillment = FulfillmentType::from($details['fulfillment_type']);
        $this->assertFulfillmentIsAllowed($cart, $fulfillment);

        $cart->fulfillment_type = $fulfillment;

        return DB::transaction(function () use ($cart, $details, $user, $fulfillment): Order {
            $this->lockAndVerifyStock($cart);

            // Price only after stock is locked, so the figures written to the
            // order match what was actually reserved.
            $totals = $this->pricing->priceCart($cart);

            $order = Order::query()->create([
                'order_number' => $this->orderNumbers->generate(),
                'user_id' => $user?->id,
                'branch_id' => $cart->branch_id,
                'voucher_id' => $totals->voucher?->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'fulfillment_type' => $fulfillment,
                'customer_first_name' => $details['customer_first_name'],
                'customer_last_name' => $details['customer_last_name'],
                'customer_email' => $details['customer_email'],
                'customer_phone' => $details['customer_phone'],
                'delivery_address' => $details['delivery_address'] ?? null,
                'notes' => $details['notes'] ?? null,
                'scheduled_for' => $details['scheduled_for'] ?? null,
                ...$totals->toDecimals(),
            ]);

            $this->writeLineItems($order, $totals);
            $this->decrementStock($cart, $totals);
            $this->recordRedemption($order, $totals, $user);

            $this->carts->clear($cart);

            return $order->load(['items', 'branch']);
        });
    }

    /**
     * @throws CheckoutException
     */
    private function assertCartIsOrderable(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        if ($cart->branch_id === null || $cart->branch === null) {
            throw CheckoutException::branchRequired();
        }

    }

    /**
     * @throws CheckoutException
     */
    private function assertFulfillmentIsAllowed(Cart $cart, FulfillmentType $fulfillment): void
    {
        if ($fulfillment === FulfillmentType::Pickup && ! $cart->branch->is_pickup_location) {
            throw CheckoutException::pickupUnavailable();
        }

        // Delivery is only offered when the cart holds something that needs it.
        if ($fulfillment === FulfillmentType::Delivery && ! $cart->requiresDelivery()) {
            throw CheckoutException::deliveryUnavailable();
        }
    }

    /**
     * Lock the stock rows for update and confirm every line can be filled.
     *
     * @throws CheckoutException
     */
    private function lockAndVerifyStock(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            if ($item->product === null) {
                continue;
            }

            $available = (int) DB::table('branch_product')
                ->where('branch_id', $cart->branch_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->value('quantity');

            if ($item->quantity > $available) {
                throw CheckoutException::insufficientStock($item->product->name, $available);
            }
        }
    }

    private function writeLineItems(Order $order, CartTotals $totals): void
    {
        foreach ($totals->lines as $line) {
            $order->items()->create([
                'product_id' => $line->product->id,
                'product_name' => $line->product->name,
                'sku' => $line->product->sku,
                'unit_price' => Money::toDecimal($line->unitPriceCentavos),
                'quantity' => $line->quantity,
                'line_total' => Money::toDecimal($line->lineTotalCentavos),
            ]);
        }
    }

    private function decrementStock(Cart $cart, CartTotals $totals): void
    {
        foreach ($totals->lines as $line) {
            DB::table('branch_product')
                ->where('branch_id', $cart->branch_id)
                ->where('product_id', $line->product->id)
                ->decrement('quantity', $line->quantity);
        }
    }

    private function recordRedemption(Order $order, CartTotals $totals, ?User $user): void
    {
        if ($totals->voucher === null || $totals->discountCentavos <= 0) {
            return;
        }

        $order->redemption()->create([
            'voucher_id' => $totals->voucher->id,
            'user_id' => $user?->id,
            'amount' => Money::toDecimal($totals->discountCentavos),
        ]);

        // Usage limits are actually enforced here; the previous system stored a
        // count but never decremented it.
        $totals->voucher->increment('times_used');
    }
}
