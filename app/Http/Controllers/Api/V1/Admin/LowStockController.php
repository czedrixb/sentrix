<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LowStockResource;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Products at or below their low-stock threshold, per branch.
 *
 * Split out of the dashboard aggregate so the panel can be paged and searched
 * without refetching every counter on the screen. It used to be a hardcoded
 * twenty-five row slice with no way to reach row twenty-six.
 */
class LowStockController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $branchId = $request->user()->branch_id;

        $rows = DB::table('branch_product')
            ->join('products', 'products.id', '=', 'branch_product.product_id')
            ->join('branches', 'branches.id', '=', 'branch_product.branch_id')
            ->whereNull('products.deleted_at')
            ->where('products.status', ProductStatus::Active->value)
            ->whereColumn('branch_product.quantity', '<=', 'branch_product.low_stock_threshold')
            ->when($branchId, fn (Builder $query) => $query->where('branch_product.branch_id', $branchId))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn (Builder $scope) => $scope
                    ->where('products.name', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('branches.name', 'like', $term));
            })
            // Scarcest first. The trailing keys only break ties, so paging stays
            // stable instead of reshuffling rows that share a quantity.
            ->orderBy('branch_product.quantity')
            ->orderBy('products.name')
            ->orderBy('branches.id')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branch_product.quantity',
                'branch_product.low_stock_threshold',
            ])
            ->paginate($request->integer('per_page', 5))
            ->withQueryString();

        return LowStockResource::collection($rows);
    }
}
