<?php

use App\Http\Controllers\Api\V1\Admin\BranchController;
use App\Http\Controllers\Api\V1\Admin\ContentController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\InquiryController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\TaxonomyController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API (v1)
|--------------------------------------------------------------------------
|
| One set of routes for all staff. Access is decided by permission, and where
| a branch matters it is decided per row by a policy. The previous system had
| fourteen near-identical route groups -- one per role, seven of them purely
| because there were seven cities -- so opening a branch meant editing the
| routes file. Here it means adding a row.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->name('api.v1.admin.')->group(function (): void {

    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    /*
    | Orders. Branch scoping is enforced by OrderPolicy on every row.
    */
    Route::middleware('permission:orders.view')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });

    Route::middleware('permission:orders.manage')->group(function (): void {
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('orders/{order}/payment', [OrderController::class, 'updatePayment'])->name('orders.payment');
        Route::post('orders/{order}/archive', [OrderController::class, 'archive'])->name('orders.archive');
        Route::delete('orders/{order}/archive', [OrderController::class, 'restore'])->name('orders.restore');
        Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    });

    /*
    | Catalogue
    */
    Route::middleware('permission:products.view')->group(function (): void {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    Route::middleware('permission:products.manage')->group(function (): void {
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::post('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::post('products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
        Route::delete('products/{product}/archive', [ProductController::class, 'restore'])->name('products.restore');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    // Stock is its own permission: a branch manager adjusts stock without
    // being able to edit the product itself.
    Route::patch('products/{product}/stock', [ProductController::class, 'updateStock'])
        ->middleware('permission:stock.manage')
        ->name('products.stock');

    /*
    | Taxonomy -- full CRUD, unlike the previous read-only categories screen.
    */
    Route::get('categories', [TaxonomyController::class, 'categories'])
        ->middleware('permission:products.view')->name('categories.index');
    Route::get('brands', [TaxonomyController::class, 'brands'])
        ->middleware('permission:products.view')->name('brands.index');

    Route::middleware('permission:categories.manage')->group(function (): void {
        Route::post('categories', [TaxonomyController::class, 'storeCategory'])->name('categories.store');
        Route::post('categories/{category}', [TaxonomyController::class, 'updateCategory'])->name('categories.update');
        Route::delete('categories/{category}', [TaxonomyController::class, 'destroyCategory'])->name('categories.destroy');
    });

    Route::middleware('permission:brands.manage')->group(function (): void {
        Route::post('brands', [TaxonomyController::class, 'storeBrand'])->name('brands.store');
        Route::post('brands/{brand}', [TaxonomyController::class, 'updateBrand'])->name('brands.update');
        Route::delete('brands/{brand}', [TaxonomyController::class, 'destroyBrand'])->name('brands.destroy');
    });

    /*
    | Branches. Creating one here is the entire cost of opening a location.
    */
    Route::get('branches', [BranchController::class, 'index'])->name('branches.index');

    Route::middleware('permission:branches.manage')->group(function (): void {
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
        Route::post('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    });

    /*
    | Vouchers
    */
    Route::get('vouchers', [VoucherController::class, 'index'])
        ->middleware('permission:vouchers.view')->name('vouchers.index');
    Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])
        ->middleware('permission:vouchers.view')->name('vouchers.show');

    Route::middleware('permission:vouchers.manage')->group(function (): void {
        Route::post('vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::patch('vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    });

    /*
    | Enquiries -- one list for every branch.
    */
    Route::middleware('permission:inquiries.view')->group(function (): void {
        Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    });

    Route::middleware('permission:inquiries.manage')->group(function (): void {
        Route::patch('inquiries/{inquiry}/handled', [InquiryController::class, 'toggleHandled'])->name('inquiries.handled');
        Route::delete('inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
    });

    /*
    | CMS content
    */
    Route::middleware('permission:content.view')->group(function (): void {
        Route::get('posts', [ContentController::class, 'posts'])->name('posts.index');
        Route::get('posts/{post}', [ContentController::class, 'showPost'])->name('posts.show');
        Route::get('banners', [ContentController::class, 'banners'])->name('banners.index');
        Route::get('gallery', [ContentController::class, 'gallery'])->name('gallery.index');
    });

    Route::middleware('permission:content.manage')->group(function (): void {
        Route::post('posts', [ContentController::class, 'storePost'])->name('posts.store');
        Route::post('posts/{post}', [ContentController::class, 'updatePost'])->name('posts.update');
        Route::delete('posts/{post}', [ContentController::class, 'destroyPost'])->name('posts.destroy');

        Route::post('banners', [ContentController::class, 'storeBanner'])->name('banners.store');
        Route::post('banners/{banner}', [ContentController::class, 'updateBanner'])->name('banners.update');
        Route::delete('banners/{banner}', [ContentController::class, 'destroyBanner'])->name('banners.destroy');

        Route::post('gallery', [ContentController::class, 'storeGalleryImages'])->name('gallery.store');
        Route::delete('gallery/{image}', [ContentController::class, 'destroyGalleryImage'])->name('gallery.destroy');
    });

    Route::middleware('permission:careers.manage')->group(function (): void {
        Route::get('careers', [ContentController::class, 'careers'])->name('careers.index');
        Route::post('careers', [ContentController::class, 'storeCareer'])->name('careers.store');
        Route::post('careers/{career}', [ContentController::class, 'updateCareer'])->name('careers.update');
        Route::delete('careers/{career}', [ContentController::class, 'destroyCareer'])->name('careers.destroy');
    });

    /*
    | Staff accounts
    */
    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('roles', [UserController::class, 'roles'])->name('roles.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
