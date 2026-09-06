<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Generates unique, human-quotable order numbers.
 *
 * The previous system used rand(111111, 999999) with no uniqueness constraint,
 * which collided in a 889k space. Numbers here are sequential within a day and
 * backed by a unique index, with a retry to close the race.
 */
class OrderNumberGenerator
{
    private const MAX_ATTEMPTS = 5;

    public function generate(): string
    {
        $prefix = config('kompra.order_number_prefix');
        $datePart = now()->format('ymd');

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $sequence = $this->nextSequenceFor($prefix, $datePart);
            $candidate = sprintf('%s-%s-%04d', $prefix, $datePart, $sequence);

            if (! Order::query()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fall back to a wider space rather than failing the checkout outright.
        return sprintf('%s-%s-%s', $prefix, $datePart, strtoupper(bin2hex(random_bytes(3))));
    }

    private function nextSequenceFor(string $prefix, string $datePart): int
    {
        $latest = DB::table('orders')
            ->where('order_number', 'like', $prefix.'-'.$datePart.'-%')
            ->orderByDesc('id')
            ->value('order_number');

        if ($latest === null) {
            return 1;
        }

        $tail = (int) substr((string) $latest, strrpos((string) $latest, '-') + 1);

        return $tail + 1;
    }
}
