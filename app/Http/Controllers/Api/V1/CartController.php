<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveCart;
use App\Http\Resources\CartResource;
use App\Models\Branch;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\Cart\CartService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;

/**
 * Cart mutation over proper verbs.
 *
 * The previous system did add, increment, decrement and remove through a single
 * GET with query flags, and read a user-supplied string as a database column
 * name to find stock.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly PricingService $pricing,
    ) {}

    public function show(Request $request): CartResource
    {
        return $this->present($request);
    }

    public function storeItem(Request $request): CartResource
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = ResolveCart::from($request);

        $this->carts->addItem(
            $cart,
            Product::query()->findOrFail($validated['product_id']),
            (int) ($validated['quantity'] ?? 1),
            Branch::query()->findOrFail($validated['branch_id']),
        );

        return $this->present($request);
    }

    public function updateItem(Request $request, CartItem $item): CartResource
    {
        $cart = ResolveCart::from($request);

        abort_unless($item->cart_id === $cart->id, 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $this->carts->updateItem($cart, $item, (int) $validated['quantity']);

        return $this->present($request);
    }

    public function destroyItem(Request $request, CartItem $item): CartResource
    {
        $cart = ResolveCart::from($request);

        abort_unless($item->cart_id === $cart->id, 404);

        $this->carts->removeItem($cart, $item);

        return $this->present($request);
    }

    public function clear(Request $request): CartResource
    {
        $this->carts->clear(ResolveCart::from($request));

        return $this->present($request);
    }

    /**
     * Attach or clear a voucher. Whether it discounts anything is decided by
     * the pricing service, which the response reflects.
     */
    public function applyVoucher(Request $request): CartResource
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:64'],
        ]);

        $voucher = filled($validated['code'] ?? null)
            ? Voucher::query()->where('code', $validated['code'])->first()
            : null;

        if (filled($validated['code'] ?? null) && $voucher === null) {
            return $this->present($request, 'That voucher code was not recognised.');
        }

        $this->carts->applyVoucher(ResolveCart::from($request), $voucher);

        return $this->present($request);
    }

    /**
     * Choose pick-up or delivery. Delivery is only offered when the cart holds
     * something that needs it.
     */
    public function setFulfillment(Request $request): CartResource
    {
        $validated = $request->validate([
            'fulfillment_type' => ['required', 'string', 'in:pickup,delivery'],
        ]);

        $cart = ResolveCart::from($request);
        $type = FulfillmentType::from($validated['fulfillment_type']);

        if ($type === FulfillmentType::Delivery) {
            $cart->loadMissing('items.product');

            abort_unless($cart->requiresDelivery(), 422, 'Delivery is not available for this cart.');
        }

        $cart->update(['fulfillment_type' => $type]);

        return $this->present($request);
    }

    /**
     * Always return the cart with freshly computed totals.
     */
    private function present(Request $request, ?string $notice = null): CartResource
    {
        $cart = ResolveCart::from($request)
            ->fresh(['items.product.images', 'voucher.products', 'branch']);

        $resource = new CartResource($cart, $this->pricing->priceCart($cart));

        return $notice === null
            ? $resource
            : $resource->additional(['notice' => $notice]);
    }
}
