<?php

namespace App\Services\Cart;

use App\Enums\ProductStatus;
use App\Exceptions\CheckoutException;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns cart lifecycle and mutation.
 *
 * Carts are persisted rows keyed by an opaque token, not PHP session state, so
 * the API stays stateless and a cart survives across devices once a customer
 * signs in.
 */
class CartService
{
    /**
     * Fetch an existing cart by token, or start a fresh one.
     */
    public function resolve(?string $token, ?User $user = null): Cart
    {
        $cart = $token !== null
            ? Cart::query()->with(['items.product', 'voucher'])->where('token', $token)->first()
            : null;

        if ($cart === null && $user !== null) {
            $cart = Cart::query()->with(['items.product', 'voucher'])
                ->where('user_id', $user->id)
                ->latest()
                ->first();
        }

        $cart ??= Cart::query()->create([
            'token' => Str::uuid()->toString(),
            'user_id' => $user?->id,
            'expires_at' => now()->addDays((int) config('kompra.cart.lifetime_days')),
        ]);

        if ($user !== null && $cart->user_id === null) {
            $cart->update(['user_id' => $user->id]);
        }

        return $cart;
    }

    /**
     * Add a product to the cart, or increase its quantity.
     *
     * A cart is always tied to a single branch: the first line fixes it, and
     * subsequent lines must match. The previous system enforced this only in a
     * Blade template, so the rule vanished the moment anyone posted directly.
     */
    public function addItem(Cart $cart, Product $product, int $quantity, Branch $branch): Cart
    {
        if ($product->status !== ProductStatus::Active) {
            throw new CheckoutException('This product is not available.', 'product_id');
        }

        if ($cart->branch_id !== null && $cart->branch_id !== $branch->id) {
            throw CheckoutException::mixedBranches();
        }

        $existing = $cart->items()->where('product_id', $product->id)->first();
        $requested = max(1, $quantity) + (int) ($existing->quantity ?? 0);

        $this->assertStockAvailable($product, $branch, $requested);

        DB::transaction(function () use ($cart, $product, $branch, $requested, $existing): void {
            if ($cart->branch_id === null) {
                $cart->update(['branch_id' => $branch->id]);
            }

            $attributes = [
                'quantity' => $requested,
                'unit_price' => $product->price,
            ];

            if ($existing !== null) {
                $existing->update($attributes);

                return;
            }

            $cart->items()->create($attributes + ['product_id' => $product->id]);
        });

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }

    /**
     * Set an exact quantity for a line. A quantity of zero removes it.
     */
    public function updateItem(Cart $cart, CartItem $item, int $quantity): Cart
    {
        if ($quantity <= 0) {
            return $this->removeItem($cart, $item);
        }

        $branch = $cart->branch;

        if ($branch !== null && $item->product !== null) {
            $this->assertStockAvailable($item->product, $branch, $quantity);
        }

        $item->update(['quantity' => $quantity]);

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }

    public function removeItem(Cart $cart, CartItem $item): Cart
    {
        $item->delete();

        $cart->refresh();

        // Releasing the last line releases the branch lock too.
        if ($cart->items()->count() === 0) {
            $cart->update(['branch_id' => null, 'voucher_id' => null, 'fulfillment_type' => null]);
        }

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }

    public function clear(Cart $cart): Cart
    {
        $cart->items()->delete();
        $cart->update(['branch_id' => null, 'voucher_id' => null, 'fulfillment_type' => null]);

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }

    /**
     * Attach a voucher. Whether it actually discounts anything is decided by
     * the pricing service, not here.
     */
    public function applyVoucher(Cart $cart, ?Voucher $voucher): Cart
    {
        $cart->update(['voucher_id' => $voucher?->id]);

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }

    /**
     * Move a guest cart onto a user account at sign-in.
     */
    public function attachToUser(Cart $cart, User $user): Cart
    {
        $cart->update(['user_id' => $user->id]);

        return $cart;
    }

    /**
     * @throws CheckoutException
     */
    private function assertStockAvailable(Product $product, Branch $branch, int $requested): void
    {
        $available = (int) DB::table('branch_product')
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        if ($requested > $available) {
            throw CheckoutException::insufficientStock($product->name, $available);
        }
    }

    /**
     * Total units in the cart, for the storefront header badge.
     */
    public function itemCount(Cart $cart): int
    {
        return (int) $cart->items()->sum('quantity');
    }

    /**
     * Snapshot unit prices onto the cart lines. Used when displaying a cart so
     * the customer sees any catalogue price change immediately.
     */
    public function refreshPrices(Cart $cart): Cart
    {
        $cart->loadMissing('items.product');

        foreach ($cart->items as $item) {
            if ($item->product === null) {
                continue;
            }

            if (Money::toCentavos($item->unit_price) !== Money::toCentavos($item->product->price)) {
                $item->update(['unit_price' => $item->product->price]);
            }
        }

        return $cart->fresh(['items.product', 'voucher', 'branch']);
    }
}
