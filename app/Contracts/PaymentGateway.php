<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * Settles payment for an order.
 *
 * Online payment is deferred. The bound implementation records the order as
 * payable at the branch; a hosted-checkout gateway can replace it later without
 * touching the checkout flow.
 */
interface PaymentGateway
{
    /**
     * Begin payment for an order.
     *
     * @return array{redirect_url: ?string, reference: ?string}
     */
    public function initiate(Order $order): array;

    /**
     * Whether this gateway sends the customer off-site to pay.
     */
    public function requiresRedirect(): bool;

    /**
     * A short identifier for the method, shown to staff and customers.
     */
    public function name(): string;
}
