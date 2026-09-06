<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Row-level authorisation for orders.
 *
 * A branch manager may only see and act on their own branch's orders. The
 * previous system scoped only the index query, so any branch admin could read
 * or mutate another branch's order simply by knowing its id.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view') && $this->withinScope($user, $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.manage') && $this->withinScope($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Staff tied to a branch see only that branch; everyone else sees all.
     */
    private function withinScope(User $user, Order $order): bool
    {
        if ($user->branch_id === null) {
            return true;
        }

        return $user->branch_id === $order->branch_id;
    }
}
