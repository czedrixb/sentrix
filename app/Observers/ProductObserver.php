<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductObserver
{
    /**
     * Give the product a stock row at every branch.
     */
    public function created(Product $product): void
    {
        $branchIds = Branch::query()->pluck('id');

        if ($branchIds->isEmpty()) {
            return;
        }

        $product->branches()->syncWithoutDetaching(
            $branchIds->mapWithKeys(fn (int $branchId): array => [$branchId => ['quantity' => 0]])->all()
        );
    }

    /**
     * Keep the slug in step with the name when one is not supplied.
     */
    public function saving(Product $product): void
    {
        if (blank($product->slug)) {
            $product->slug = Str::slug($product->name);
        }
    }
}
