<?php

namespace Database\Seeders;

use App\Enums\VoucherType;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Live promotional codes.
 *
 * Between them these cover all four VoucherType cases, so the pricing rules stay
 * exercisable from the storefront without a fixture having to be invented.
 */
class VoucherSeeder extends Seeder
{
    /**
     * `products` restricts a voucher to the listed SKUs; an empty list applies
     * it cart-wide.
     *
     * @var list<array{code: string, description: string, type: VoucherType, value: float, min_quantity: ?int, min_subtotal: ?float, usage_limit: ?int, times_used: int, starts_days_ago: int, ends_in_days: ?int, is_active: bool, products: list<string>}>
     */
    private const VOUCHERS = [
        [
            'code' => 'WELCOME10',
            'description' => '10% off your first order, on any item in the catalogue',
            'type' => VoucherType::Percentage, 'value' => 10,
            'min_quantity' => null, 'min_subtotal' => null,
            'usage_limit' => 500, 'times_used' => 137,
            'starts_days_ago' => 120, 'ends_in_days' => 180, 'is_active' => true,
            'products' => [],
        ],
        [
            'code' => 'INK500',
            'description' => '₱500 off when you spend ₱3,000 or more on ink and toner',
            'type' => VoucherType::Fixed, 'value' => 500,
            'min_quantity' => null, 'min_subtotal' => 3000,
            'usage_limit' => 300, 'times_used' => 84,
            'starts_days_ago' => 45, 'ends_in_days' => 45, 'is_active' => true,
            'products' => [
                'EPS-003BK', 'EPS-664SET', 'CAN-GI790BK', 'BRO-BT5000SET', 'BRO-BTD60BK',
                'HP-GT52SET', 'HP-682TRI', 'HP-Q2612A', 'CAN-CRG337', 'KYO-TK1175',
                'RIC-SP200', 'KON-TN116', 'SAM-MLTD111S', 'OKI-45807111', 'RIS-S4253',
            ],
        ],
        [
            'code' => 'BULKPAPER15',
            'description' => '15% off bond paper when you buy 10 reams or more',
            'type' => VoucherType::PercentageMinQuantity, 'value' => 15,
            'min_quantity' => 10, 'min_subtotal' => null,
            'usage_limit' => null, 'times_used' => 46,
            'starts_days_ago' => 90, 'ends_in_days' => null, 'is_active' => true,
            'products' => ['PAP-A4S20', 'PAP-LGLS20'],
        ],
        [
            'code' => 'SCHOOLSET',
            'description' => '₱1,000 off duplicator ink and masters when you buy any two',
            'type' => VoucherType::FixedMinQuantity, 'value' => 1000,
            'min_quantity' => 2, 'min_subtotal' => null,
            'usage_limit' => 150, 'times_used' => 22,
            'starts_days_ago' => 30, 'ends_in_days' => 60, 'is_active' => true,
            'products' => ['RIS-S4253', 'PAP-RISOMSTR'],
        ],
        [
            'code' => 'BACKTOSCHOOL',
            'description' => '12% off printers and paper — June enrolment promotion (ended)',
            'type' => VoucherType::Percentage, 'value' => 12,
            'min_quantity' => null, 'min_subtotal' => null,
            'usage_limit' => 400, 'times_used' => 400,
            'starts_days_ago' => 210, 'ends_in_days' => -150, 'is_active' => false,
            'products' => [],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->upsertVouchers();
        });
    }

    private function upsertVouchers(): void
    {
        $productIds = Product::query()->pluck('id', 'sku');

        foreach (self::VOUCHERS as $row) {
            $voucher = Voucher::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'value' => $row['value'],
                    'min_quantity' => $row['min_quantity'],
                    'min_subtotal' => $row['min_subtotal'],
                    'usage_limit' => $row['usage_limit'],
                    'times_used' => $row['times_used'],
                    'starts_at' => now()->subDays($row['starts_days_ago'])->startOfDay(),
                    'ends_at' => $row['ends_in_days'] === null
                        ? null
                        : now()->addDays($row['ends_in_days'])->endOfDay(),
                    'is_active' => $row['is_active'],
                ]
            );

            $voucher->products()->sync(
                collect($row['products'])
                    ->map(fn (string $sku): ?int => $productIds[$sku] ?? null)
                    ->filter()
                    ->all()
            );
        }
    }
}
