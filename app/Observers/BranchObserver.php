<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Support\Str;

class BranchObserver
{
    /**
     * Give the branch a stock row for every existing product.
     *
     * Adding a branch is a data operation, not a code change: every product in
     * the catalogue picks up a zero-quantity row here so the new branch is
     * immediately visible to stock management without a migration.
     */
    public function created(Branch $branch): void
    {
        Product::query()
            ->select('id')
            ->chunkById(500, function ($products) use ($branch): void {
                $branch->products()->syncWithoutDetaching(
                    $products->pluck('id')->mapWithKeys(
                        fn (int $productId): array => [$productId => ['quantity' => 0]]
                    )->all()
                );
            });
    }

    /**
     * Keep the slug in step with the name when one is not supplied.
     */
    public function saving(Branch $branch): void
    {
        if (blank($branch->slug)) {
            $branch->slug = Str::slug($branch->name);
        }
    }
}
