<?php

namespace App\Services\Pricing;

use App\Models\Voucher;
use App\Support\Money;

/**
 * The authoritative money figures for a cart. The API returns these to the SPA
 * for display and writes these same figures onto the order; a total supplied by
 * the client is never read.
 */
final class CartTotals
{
    /**
     * @param  list<PricedLine>  $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly int $subtotalCentavos,
        public readonly int $discountCentavos,
        public readonly int $deliveryFeeCentavos,
        public readonly ?Voucher $voucher = null,
        public readonly ?string $voucherRejectionReason = null,
    ) {}

    public function grandTotalCentavos(): int
    {
        return max(0, $this->subtotalCentavos - $this->discountCentavos) + $this->deliveryFeeCentavos;
    }

    public function totalQuantity(): int
    {
        return array_sum(array_map(fn (PricedLine $line): int => $line->quantity, $this->lines));
    }

    /**
     * Decimal figures ready for persisting on an order.
     *
     * @return array{subtotal: string, discount_total: string, delivery_fee: string, grand_total: string}
     */
    public function toDecimals(): array
    {
        return [
            'subtotal' => Money::toDecimal($this->subtotalCentavos),
            'discount_total' => Money::toDecimal($this->discountCentavos),
            'delivery_fee' => Money::toDecimal($this->deliveryFeeCentavos),
            'grand_total' => Money::toDecimal($this->grandTotalCentavos()),
        ];
    }
}
