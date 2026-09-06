<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard figures.
     *
     * These are aggregate queries. The previous dashboard ran up to thirty-five
     * separate `Order::where(...)->get()` calls inside the Blade view and then
     * counted the resulting collections in PHP.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $user->branch_id;

        $ordersByStatus = Order::query()
            ->notArchived()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status');

        $counts = [];

        foreach (OrderStatus::cases() as $status) {
            $counts[$status->value] = [
                'label' => $status->label(),
                'total' => (int) ($ordersByStatus[$status->value] ?? 0),
            ];
        }

        return response()->json([
            'data' => [
                'scope' => $branchId !== null
                    ? Branch::query()->find($branchId)?->name
                    : 'All branches',
                'orders_by_status' => $counts,
                'orders_unpaid' => Order::query()
                    ->notArchived()
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('payment_status', PaymentStatus::Pending)
                    ->count(),
                'revenue_this_month' => (string) Order::query()
                    ->where('payment_status', PaymentStatus::Paid)
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('grand_total'),
                'products_active' => Product::query()->where('status', ProductStatus::Active)->count(),
                'inquiries_unhandled' => Inquiry::query()
                    ->unhandled()
                    ->when($branchId, fn ($query) => $query->where(function ($scope) use ($branchId): void {
                        $scope->where('branch_id', $branchId)->orWhereNull('branch_id');
                    }))
                    ->count(),
                'low_stock' => $this->lowStock($branchId),
            ],
        ]);
    }

    /**
     * Products at or below their low-stock threshold, per branch.
     *
     * @return list<array<string, mixed>>
     */
    private function lowStock(?int $branchId): array
    {
        return DB::table('branch_product')
            ->join('products', 'products.id', '=', 'branch_product.product_id')
            ->join('branches', 'branches.id', '=', 'branch_product.branch_id')
            ->whereNull('products.deleted_at')
            ->where('products.status', ProductStatus::Active->value)
            ->whereColumn('branch_product.quantity', '<=', 'branch_product.low_stock_threshold')
            ->when($branchId, fn ($query) => $query->where('branch_product.branch_id', $branchId))
            ->orderBy('branch_product.quantity')
            ->limit(25)
            ->get([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'branch_product.quantity',
                'branch_product.low_stock_threshold',
            ])
            ->map(fn ($row): array => (array) $row)
            ->all();
    }
}
