<?php

namespace App\Contracts;

use App\Models\Cart;

/**
 * Quotes a delivery fee for a cart.
 *
 * Third-party delivery is deferred, so the bound implementation returns zero.
 * A real courier integration slots in here without the checkout flow changing.
 */
interface DeliveryQuoter
{
    /**
     * The delivery fee as a decimal amount.
     */
    public function quote(Cart $cart): string;

    /**
     * Whether delivery can currently be offered at all.
     */
    public function isAvailable(): bool;
}
