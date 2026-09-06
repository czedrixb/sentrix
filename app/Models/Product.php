<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'sku', 'name', 'slug', 'brand_id', 'category_id', 'short_description', 'description',
    'price', 'is_featured', 'is_bundle', 'requires_delivery',
    'length_cm', 'width_cm', 'height_cm', 'weight_kg', 'status',
])]
#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_bundle' => 'boolean',
            'requires_delivery' => 'boolean',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:3',
            'status' => ProductStatus::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * The inclusions listed under this product when it is a bundle.
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id')->orderBy('position');
    }

    /**
     * Per-branch stock levels.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot(['quantity', 'low_stock_threshold'])
            ->withTimestamps();
    }

    public function vouchers(): BelongsToMany
    {
        return $this->belongsToMany(Voucher::class);
    }

    /**
     * Stock on hand at a given branch.
     */
    public function stockAt(int $branchId): int
    {
        $branch = $this->branches->firstWhere('id', $branchId)
            ?? $this->branches()->where('branches.id', $branchId)->first();

        return (int) ($branch?->pivot->quantity ?? 0);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProductStatus::Active);
    }

    /**
     * Restrict to products carrying stock at the given branch.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeAvailableAt(Builder $query, int $branchId): void
    {
        $query->whereHas('branches', function (Builder $branchQuery) use ($branchId): void {
            $branchQuery->where('branches.id', $branchId)->where('branch_product.quantity', '>', 0);
        });
    }

    /**
     * Select the stock level at one branch as an `available_quantity` column,
     * so a product listing does not need a query per row.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeWithAvailableQuantityAt(Builder $query, int $branchId): void
    {
        $query->select('products.*')->addSelect([
            'available_quantity' => DB::table('branch_product')
                ->select('quantity')
                ->whereColumn('branch_product.product_id', 'products.id')
                ->where('branch_product.branch_id', $branchId)
                ->limit(1),
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
