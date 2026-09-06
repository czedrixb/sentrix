<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Customer-facing order access.
 *
 * Guests look an order up by number plus the email it was placed with, which
 * replaces the previous system's reliance on a session cookie surviving a
 * third-party redirect.
 */
class OrderController extends Controller
{
    /**
     * Orders belonging to the signed-in customer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'branch'])
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return OrderResource::collection($orders);
    }

    /**
     * Look up a single order without an account.
     */
    public function lookup(Request $request): OrderResource
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::query()
            ->where('order_number', $validated['order_number'])
            ->where('customer_email', $validated['email'])
            ->with(['items', 'branch'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    /**
     * A signed-in customer's own order.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()?->id, 404);

        return new OrderResource($order->load(['items', 'branch']));
    }
}
