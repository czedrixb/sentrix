<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Services\Cart\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the caller's cart from an opaque cookie and hands it to the
 * controller.
 *
 * The cart lives in the database, not the session, so the API stays stateless
 * and a cart can outlive a session or follow a customer onto their account.
 */
class ResolveCart
{
    public function __construct(private readonly CartService $carts) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('sentrix.cart.cookie');

        $cart = $this->carts->resolve(
            $request->cookie($cookieName) ?: $request->header('X-Cart-Token'),
            $request->user()
        );

        $request->attributes->set('cart', $cart);

        $response = $next($request);

        return $response->withCookie(cookie(
            name: $cookieName,
            value: $cart->token,
            minutes: (int) config('sentrix.cart.lifetime_days') * 24 * 60,
            httpOnly: true,
            sameSite: 'lax',
        ));
    }

    /**
     * Read the cart the middleware resolved for this request.
     */
    public static function from(Request $request): Cart
    {
        return $request->attributes->get('cart');
    }
}
