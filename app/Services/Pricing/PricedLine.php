<?php

namespace App\Services\Pricing;

use App\Models\Product;

/**
 * A single priced cart line. Prices are resolved from the catalogue at pricing
 * time; nothing here is ever taken from the client.
 */
final class PricedLine
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
        public readonly int $unitPriceCentavos,
        public readonly int $lineTotalCentavos,
        public readonly bool $isVoucherEligible,
    ) {}
}
