<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveCart;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Session-cookie authentication for the SPA (Sanctum stateful mode).
 *
 * One users table and one guard serve both customers and staff; what a user may
 * do is decided by roles and permissions, not by which login form they used.
 */
class AuthController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function register(Request $request): JsonResponse
    {
        $this->assertStateful($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^9\d{9}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        $user->assignRole('customer');

        Auth::login($user);
        $request->session()->regenerate();

        $this->adoptGuestCart($request, $user);

        return response()->json(['user' => new UserResource($user->load(['branch', 'roles', 'permissions', 'roles.permissions']))], 201);
    }

    /**
     * Sign in. Throttled by email and IP, and it always reports a failure --
     * the previous implementation returned null on bad credentials, rendering
     * a blank page with no error.
     */
    public function login(Request $request): JsonResponse
    {
        $this->assertStateful($request);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $throttleKey = mb_strtolower($validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many sign-in attempts. Try again in '
                    .RateLimiter::availableIn($throttleKey).' seconds.'],
            ]);
        }

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        $this->adoptGuestCart($request, $user);

        return response()->json(['user' => new UserResource($user->load(['branch', 'roles', 'permissions', 'roles.permissions']))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->assertStateful($request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    /**
     * The current user, their roles and their permissions. The SPA uses this to
     * decide what to show; there are no server-side role redirects.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load(['branch', 'roles', 'permissions', 'roles.permissions'])),
        ]);
    }

    /**
     * Session-cookie authentication only works on a stateful request.
     *
     * Sanctum makes a request stateful by matching its Origin or Referer against
     * SANCTUM_STATEFUL_DOMAINS; a caller that sends neither gets no session, and
     * regenerating one then fails deep inside the framework with a 500. Say what
     * is actually wrong instead.
     */
    private function assertStateful(Request $request): void
    {
        abort_unless(
            $request->hasSession(),
            419,
            'This endpoint needs a stateful request. Call /sanctum/csrf-cookie first, '
                .'then send the session and XSRF-TOKEN cookies with an Origin this app trusts.',
        );
    }

    /**
     * Carry a guest cart over to the account being signed into.
     */
    private function adoptGuestCart(Request $request, User $user): void
    {
        if (! $request->attributes->has('cart')) {
            return;
        }

        $this->carts->attachToUser(ResolveCart::from($request), $user);
    }
}
