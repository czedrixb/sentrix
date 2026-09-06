<?php

namespace App\Services\Delivery;

use App\Contracts\DeliveryQuoter;
use App\Models\Cart;

/**
 * Stand-in quoter used while courier integration is deferred.
 *
 * Delivery is still offered for products flagged requires_delivery, but it is
 * arranged and charged by the branch rather than quoted online.
 */
class NoDeliveryQuoter implements DeliveryQuoter
{
    public function quote(Cart $cart): string
    {
        return '0.00';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
