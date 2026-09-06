<?php

namespace App\Services\Pricing;

use App\Contracts\DeliveryQuoter;
use App\Enums\FulfillmentType;
use App\Enums\VoucherType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Voucher;
use App\Support\Money;

/**
 * The single source of truth for cart money.
 *
 * In the previous system the discount arithmetic lived inside a Blade template
 * and the resulting total was posted back to the server as a hidden input, so
 * the browser decided what to charge. Every figure here is derived from the
 * catalogue and the voucher record instead, and the API never accepts a total
 * from the client.
 */
class PricingService
{
    public function __construct(private readonly DeliveryQuoter $deliveryQuoter) {}

    /**
     * Price a cart, applying its voucher only if the voucher genuinely qualifies.
     */
    public function priceCart(Cart $cart): CartTotals
    {
        $cart->loadMissing(['items.product', 'voucher.products', 'branch']);

        $voucher = $cart->voucher;
        $lines = $this->buildLines($cart, $voucher);

        $subtotal = array_sum(array_map(
            fn (PricedLine $line): int => $line->lineTotalCentavos,
            $lines
        ));

        $rejection = $voucher !== null ? $this->rejectionReason($voucher, $lines, $subtotal) : null;
        $discount = $rejection === null && $voucher !== null
            ? $this->discountFor($voucher, $lines, $subtotal)
            : 0;

        $deliveryFee = $cart->fulfillment_type === FulfillmentType::Delivery
            ? Money::toCentavos($this->deliveryQuoter->quote($cart))
            : 0;

        return new CartTotals(
            lines: $lines,
            subtotalCentavos: $subtotal,
            discountCentavos: $discount,
            deliveryFeeCentavos: $deliveryFee,
            voucher: $rejection === null ? $voucher : null,
            voucherRejectionReason: $rejection,
        );
    }

    /**
     * Resolve each line's price from the catalogue, not from the stored snapshot.
     *
     * @return list<PricedLine>
     */
    private function buildLines(Cart $cart, ?Voucher $voucher): array
    {
        $restrictedTo = $voucher?->products->pluck('id');
        $appliesToEverything = $restrictedTo === null || $restrictedTo->isEmpty();

        return $cart->items
            ->filter(fn (CartItem $item): bool => $item->product !== null)
            ->map(function (CartItem $item) use ($appliesToEverything, $restrictedTo): PricedLine {
                $unitPrice = Money::toCentavos($item->product->price);
                $quantity = max(0, (int) $item->quantity);

                return new PricedLine(
                    product: $item->product,
                    quantity: $quantity,
                    unitPriceCentavos: $unitPrice,
                    lineTotalCentavos: $unitPrice * $quantity,
                    isVoucherEligible: $appliesToEverything || $restrictedTo->contains($item->product->id),
                );
            })
            ->values()
            ->all();
    }

    /**
     * Why the voucher cannot be applied, or null when it can.
     *
     * @param  list<PricedLine>  $lines
     */
    private function rejectionReason(Voucher $voucher, array $lines, int $subtotal): ?string
    {
        if (! $voucher->isRedeemable()) {
            return 'This voucher is no longer available.';
        }

        $eligible = $this->eligibleLines($lines);

        if ($eligible === []) {
            return 'This voucher does not apply to any item in your cart.';
        }

        if ($voucher->min_subtotal !== null && $subtotal < Money::toCentavos($voucher->min_subtotal)) {
            return 'Your cart has not reached the minimum spend for this voucher.';
        }

        if ($voucher->type->requiresMinimumQuantity()) {
            $eligibleQuantity = array_sum(array_map(
                fn (PricedLine $line): int => $line->quantity,
                $eligible
            ));

            if ($eligibleQuantity < (int) $voucher->min_quantity) {
                return 'Add more qualifying items to use this voucher.';
            }
        }

        return null;
    }

    /**
     * The discount in centavos.
     *
     * The discount is computed once against the eligible base. The previous
     * implementation accumulated inside a per-item loop, which multiplied a
     * percentage discount by the number of items in the cart.
     *
     * @param  list<PricedLine>  $lines
     */
    private function discountFor(Voucher $voucher, array $lines, int $subtotal): int
    {
        $base = array_sum(array_map(
            fn (PricedLine $line): int => $line->lineTotalCentavos,
            $this->eligibleLines($lines)
        ));

        $discount = match ($voucher->type) {
            VoucherType::Percentage,
            VoucherType::PercentageMinQuantity => Money::percentageOf($base, $voucher->value),
            VoucherType::Fixed,
            VoucherType::FixedMinQuantity => Money::toCentavos($voucher->value),
        };

        // A discount can never exceed the value of the items it applies to.
        return Money::clamp($discount, 0, min($base, $subtotal));
    }

    /**
     * @param  list<PricedLine>  $lines
     * @return list<PricedLine>
     */
    private function eligibleLines(array $lines): array
    {
        return array_values(array_filter(
            $lines,
            fn (PricedLine $line): bool => $line->isVoucherEligible && $line->quantity > 0
        ));
    }
}
