<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Paid revenue over time, bucketed for the dashboard chart.
 *
 * The database only ever groups by date. Rolling those days up into weeks or
 * months happens here, in PHP, because the SQL for it differs between MySQL and
 * SQLite and the test suite runs on the latter. A day-grouped query returns at
 * most 366 rows for the longest window on offer, so the rollup is cheap.
 */
class RevenueSeriesController extends Controller
{
    /**
     * Selectable windows. Keyed by the value the client sends.
     *
     * @var array<string, array{unit: string, count: int, label: string}>
     */
    private const RANGES = [
        '30d' => ['unit' => 'day', 'count' => 30, 'label' => 'Last 30 days'],
        '12w' => ['unit' => 'week', 'count' => 12, 'label' => 'Last 12 weeks'],
        '12m' => ['unit' => 'month', 'count' => 12, 'label' => 'Last 12 months'],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $key = $request->string('range')->toString();
        $key = array_key_exists($key, self::RANGES) ? $key : '12w';

        ['unit' => $unit, 'count' => $count, 'label' => $label] = self::RANGES[$key];

        $starts = $this->bucketStarts($unit, $count);
        $totals = array_fill_keys(array_map($this->format(...), $starts), 0.0);

        foreach ($this->dailyTotals($request, $starts[0]) as $day => $total) {
            $bucket = $this->format($this->startOf($unit, CarbonImmutable::parse((string) $day)));

            // A row can only fall outside the window through clock skew, but
            // dropping it is safer than creating a bucket the axis never drew.
            if (array_key_exists($bucket, $totals)) {
                $totals[$bucket] += (float) $total;
            }
        }

        return response()->json([
            'data' => [
                'range' => $key,
                'unit' => $unit,
                'label' => $label,
                // Rounded on the way out: these are sums of floats, and without
                // it a quiet bucket can serialise as 3234.5600000000004.
                'total' => (string) round(array_sum($totals), 2),
                'points' => array_map(
                    fn (CarbonImmutable $start): array => [
                        'key' => $this->format($start),
                        'label' => $this->shortLabel($unit, $start),
                        'full_label' => $this->fullLabel($unit, $start),
                        'value' => (string) round($totals[$this->format($start)], 2),
                    ],
                    $starts,
                ),
            ],
        ]);
    }

    /**
     * Revenue per calendar day since the window opened, keyed by date.
     *
     * @return Collection<string, string>
     */
    private function dailyTotals(Request $request, CarbonImmutable $from): Collection
    {
        $branchId = $request->user()->branch_id;

        return Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
    }

    /**
     * The ordered bucket start dates, oldest first, ending on the current one.
     *
     * @return list<CarbonImmutable>
     */
    private function bucketStarts(string $unit, int $count): array
    {
        $current = $this->startOf($unit, CarbonImmutable::now());

        $starts = [];

        for ($step = $count - 1; $step >= 0; $step--) {
            $starts[] = match ($unit) {
                'day' => $current->subDays($step),
                'week' => $current->subWeeks($step),
                default => $current->subMonths($step),
            };
        }

        return $starts;
    }

    private function startOf(string $unit, CarbonImmutable $date): CarbonImmutable
    {
        return match ($unit) {
            'day' => $date->startOfDay(),
            'week' => $date->startOfWeek(),
            default => $date->startOfMonth(),
        };
    }

    private function format(CarbonImmutable $date): string
    {
        return $date->format('Y-m-d');
    }

    /** Axis tick: short enough that twelve of them fit without rotating. */
    private function shortLabel(string $unit, CarbonImmutable $start): string
    {
        return match ($unit) {
            'month' => $start->format('M'),
            default => $start->format('j M'),
        };
    }

    /** Tooltip heading, where there is room to be unambiguous. */
    private function fullLabel(string $unit, CarbonImmutable $start): string
    {
        return match ($unit) {
            'day' => $start->format('j M Y'),
            'week' => $start->format('j M').' – '.$start->endOfWeek()->format('j M Y'),
            default => $start->format('F Y'),
        };
    }
}
