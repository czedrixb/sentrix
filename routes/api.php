<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CareerController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront API (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    // Reference data. Nothing here is hardcoded: branches, categories and
    // brands are rows, so new ones appear without a deployment.
    Route::get('branches', [ReferenceController::class, 'branches'])->name('branches');
    Route::get('categories', [ReferenceController::class, 'categories'])->name('categories');
    Route::get('brands', [ReferenceController::class, 'brands'])->name('brands');
    Route::get('banners', [ReferenceController::class, 'banners'])->name('banners');
    Route::get('gallery', [ReferenceController::class, 'gallery'])->name('gallery');

    // Catalogue
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('products/{product}/related', [ProductController::class, 'related'])->name('products.related');

    // Content
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::get('careers', [CareerController::class, 'index'])->name('careers.index');
    Route::get('careers/{career}', [CareerController::class, 'show'])->name('careers.show');

    Route::post('inquiries', [InquiryController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('inquiries.store');

    // Cart and checkout. Every route carries the cart cookie.
    Route::middleware('cart')->group(function (): void {
        Route::get('cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('cart/items', [CartController::class, 'storeItem'])->name('cart.items.store');
        Route::patch('cart/items/{item}', [CartController::class, 'updateItem'])->name('cart.items.update');
        Route::delete('cart/items/{item}', [CartController::class, 'destroyItem'])->name('cart.items.destroy');
        Route::delete('cart', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('cart/voucher', [CartController::class, 'applyVoucher'])->name('cart.voucher');
        Route::post('cart/fulfillment', [CartController::class, 'setFulfillment'])->name('cart.fulfillment');

        Route::post('orders', [CheckoutController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('orders.store');

        // Authentication, so a guest cart follows the customer onto their account.
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login');
    });

    Route::post('orders/lookup', [OrderController::class, 'lookup'])
        ->middleware('throttle:20,1')
        ->name('orders.lookup');

    /*
    |----------------------------------------------------------------------
    | Authenticated
    |----------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
});
