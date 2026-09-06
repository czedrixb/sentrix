<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Returns an order's reserved stock to its branch.
 *
 * Stock is committed when an order is placed, so cancelling has to give it
 * back. The previous system decremented on completion and never restored.
 */
class RestockOrder
{
    public function handle(Order $order): void
    {
        $order->loadMissing('items');

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    continue;
                }

                DB::table('branch_product')
                    ->where('branch_id', $order->branch_id)
                    ->where('product_id', $item->product_id)
                    ->increment('quantity', $item->quantity);
            }
        });
    }
}
