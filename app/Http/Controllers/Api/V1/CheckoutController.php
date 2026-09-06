<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\PaymentGateway;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveCart;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\Orders\PlaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PlaceOrder $placeOrder,
        private readonly PaymentGateway $paymentGateway,
    ) {}

    /**
     * Place an order from the caller's cart.
     *
     * Totals are recomputed here from the catalogue, so the response reflects
     * what the server decided, never what the client sent.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $cart = ResolveCart::from($request);

        try {
            $order = $this->placeOrder->handle($cart, $request->validated(), $request->user());
        } catch (CheckoutException $exception) {
            throw ValidationException::withMessages([
                $exception->field => [$exception->getMessage()],
            ]);
        }

        $payment = $this->paymentGateway->initiate($order);

        return (new OrderResource($order->load(['items', 'branch'])))
            ->additional([
                'payment' => [
                    'method' => $this->paymentGateway->name(),
                    'requires_redirect' => $this->paymentGateway->requiresRedirect(),
                    'redirect_url' => $payment['redirect_url'],
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }
}
