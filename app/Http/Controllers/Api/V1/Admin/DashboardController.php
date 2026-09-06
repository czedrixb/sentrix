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
            ],
        ]);
    }
}
