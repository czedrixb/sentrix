<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Raised when a cart cannot be mutated or turned into an order.
 *
 * It renders itself as a 422 with a field key so the SPA can attach the message
 * to the right control, rather than surfacing as a 500.
 */
class CheckoutException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'cart')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [$this->field => [$this->getMessage()]],
        ], 422);
    }

    public static function emptyCart(): self
    {
        return new self('Your cart is empty.', 'cart');
    }

    public static function mixedBranches(): self
    {
        return new self('All items in an order must come from the same branch.', 'branch_id');
    }

    public static function branchRequired(): self
    {
        return new self('Choose a branch before checking out.', 'branch_id');
    }

    public static function insufficientStock(string $productName, int $available): self
    {
        return new self(
            $available > 0
                ? sprintf('Only %d of "%s" remain at this branch.', $available, $productName)
                : sprintf('"%s" is out of stock at this branch.', $productName),
            'quantity'
        );
    }

    public static function deliveryUnavailable(): self
    {
        return new self('Delivery is not available for this order.', 'fulfillment_type');
    }

    public static function pickupUnavailable(): self
    {
        return new self('This branch does not offer pick-up.', 'fulfillment_type');
    }
}
