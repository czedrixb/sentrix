<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;

/**
 * Orders are placed online and settled at the branch on pick-up or delivery.
 */
class PayAtBranchGateway implements PaymentGateway
{
    /**
     * @return array{redirect_url: ?string, reference: ?string}
     */
    public function initiate(Order $order): array
    {
        return [
            'redirect_url' => null,
            'reference' => $order->order_number,
        ];
    }

    public function requiresRedirect(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'pay_at_branch';
    }
}
