<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\RestockOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly RestockOrder $restock) {}

    /**
     * Paginated, filtered order list.
     *
     * Branch staff are scoped to their own branch by the query here and by the
     * policy on every single-order action.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $user = $request->user();

        $orders = Order::query()
            ->with(['branch', 'items'])
            ->when($user->branch_id, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
            ->when($request->filled('branch_id'), fn (Builder $q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('fulfillment_type'), fn (Builder $q) => $q->where('fulfillment_type', $request->string('fulfillment_type')))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn (Builder $search) => $search
                    ->where('order_number', 'like', $term)
                    ->orWhere('customer_email', 'like', $term)
                    ->orWhere('customer_last_name', 'like', $term)
                    ->orWhere('customer_first_name', 'like', $term));
            })
            ->when(
                $request->boolean('archived'),
                fn (Builder $query) => $query->archived(),
                fn (Builder $query) => $query->notArchived()
            )
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['items', 'branch']));
    }

    /**
     * Move an order through its lifecycle.
     *
     * Cancelling returns the reserved stock, which the previous system never
     * did in either direction.
     */
    public function updateStatus(Request $request, Order $order): OrderResource
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $next = OrderStatus::from($validated['status']);

        if ($next === OrderStatus::Cancelled && $order->status !== OrderStatus::Cancelled) {
            $this->restock->handle($order);
        }

        $order->update(['status' => $next]);

        return new OrderResource($order->fresh(['items', 'branch']));
    }

    /**
     * Record payment taken at the branch.
     */
    public function updatePayment(Request $request, Order $order): OrderResource
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
        ]);

        $status = PaymentStatus::from($validated['payment_status']);

        $order->update([
            'payment_status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);

        return new OrderResource($order->fresh(['items', 'branch']));
    }

    /**
     * Archive or restore. An explicit endpoint, rather than the previous
     * system's PUT with a hidden `archive=yes` field.
     */
    public function archive(Order $order): OrderResource
    {
        $this->authorize('update', $order);

        $order->update(['archived_at' => now()]);

        return new OrderResource($order->fresh(['items', 'branch']));
    }

    public function restore(Order $order): OrderResource
    {
        $this->authorize('update', $order);

        $order->update(['archived_at' => null]);

        return new OrderResource($order->fresh(['items', 'branch']));
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return response()->json(['message' => 'Order deleted.']);
    }
}
