<?php

namespace Database\Seeders;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A trading history for the admin side: customer accounts, three months of
 * orders across every status and branch, and the enquiries that came in
 * alongside them.
 *
 * This is sample activity, not reference data -- DatabaseSeeder keeps it out of
 * production. Without it the dashboard, the orders list and the enquiry inbox
 * all render as empty states, which tells nobody anything.
 */
class SalesSeeder extends Seeder
{
    /**
     * Customers. Those marked `account` get a sign-in, so the "My orders" page
     * has something behind it; the rest ordered as guests.
     *
     * @var array<string, array{first: string, last: string, email: string, phone: string, account: bool}>
     */
    private const CUSTOMERS = [
        'santos' => ['first' => 'Maria Elena', 'last' => 'Santos', 'email' => 'maria.santos@example.ph', 'phone' => '9171234567', 'account' => true],
        'delacruz' => ['first' => 'Ramon', 'last' => 'Dela Cruz', 'email' => 'ramon.delacruz@example.ph', 'phone' => '9182345678', 'account' => true],
        'lim' => ['first' => 'Grace', 'last' => 'Lim', 'email' => 'grace.lim@example.ph', 'phone' => '9273456789', 'account' => true],
        'abadilla' => ['first' => 'Joseph', 'last' => 'Abadilla', 'email' => 'joseph.abadilla@example.ph', 'phone' => '9954567890', 'account' => true],
        'reyes' => ['first' => 'Anna Liza', 'last' => 'Reyes', 'email' => 'annaliza.reyes@example.ph', 'phone' => '9065678901', 'account' => true],
        'tan' => ['first' => 'Benedict', 'last' => 'Tan', 'email' => 'benedict.tan@example.ph', 'phone' => '9176789012', 'account' => false],
        'macaraeg' => ['first' => 'Divina', 'last' => 'Macaraeg', 'email' => 'divina.macaraeg@example.ph', 'phone' => '9187890123', 'account' => false],
        'ongkiko' => ['first' => 'Paolo', 'last' => 'Ongkiko', 'email' => 'paolo.ongkiko@example.ph', 'phone' => '9278901234', 'account' => false],
    ];

    /**
     * Orders, newest last. `days_ago` places the order; totals are computed from
     * the live catalogue price and the voucher's own rule rather than written
     * down, so the arithmetic on every row is genuinely consistent.
     *
     * @var list<array{customer: string, branch: string, status: OrderStatus, payment: PaymentStatus, fulfillment: FulfillmentType, days_ago: int, items: list<array{0: string, 1: int}>, voucher?: string, delivery_fee?: float, notes?: string, archived?: bool}>
     */
    private const ORDERS = [
        // --- Older, settled business ----------------------------------------
        ['customer' => 'santos', 'branch' => 'davao-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 86,
            'items' => [['HIK-7104NIQ14P', 1], ['HIK-2CD1043G2', 4], ['SEA-SKYHAWK2TB', 1]], 'voucher' => 'WELCOME10', 'archived' => true],
        ['customer' => 'tan', 'branch' => 'cebu-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 79,
            'items' => [['HIK-2CE16D0T', 4], ['CBL-RG59SIAM', 1]]],
        ['customer' => 'delacruz', 'branch' => 'quezon-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 71,
            'items' => [['HIK-7616NIK216P', 1], ['SEA-SKYHAWK8TB', 2]], 'delivery_fee' => 850, 'notes' => 'Deliver to the 4th floor admin office. Lift access available until 5pm.', 'archived' => true],
        ['customer' => 'macaraeg', 'branch' => 'iloilo-city', 'status' => OrderStatus::Cancelled, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 68,
            'items' => [['DAH-NVR4108HS8P', 1]], 'notes' => 'Customer called to cancel — bought locally instead.'],
        ['customer' => 'lim', 'branch' => 'cebu-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 62,
            'items' => [['HIK-2CE16D0T', 12], ['HIK-2CE76D0T', 6]], 'voucher' => 'BULKCAM15'],
        ['customer' => 'abadilla', 'branch' => 'general-santos-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 55,
            'items' => [['HIK-2DE4225IW', 1]], 'delivery_fee' => 1500, 'notes' => 'Pole mount and commissioning requested.'],
        ['customer' => 'reyes', 'branch' => 'davao-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 51,
            'items' => [['DAH-HDW1439T1', 2], ['PSU-POEINJ', 1]]],
        ['customer' => 'ongkiko', 'branch' => 'malaybalay-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 47,
            'items' => [['UNV-NVR30104LBP', 1], ['UNV-2124LB', 4]]],
        ['customer' => 'santos', 'branch' => 'davao-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Refunded, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 44,
            'items' => [['DAH-HACB1A21', 3]], 'notes' => 'Analogue cameras bought for an IP recorder. Refunded in full at the counter.'],
        ['customer' => 'delacruz', 'branch' => 'quezon-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 40,
            'items' => [['BDL-SHOP4', 1]]],
        ['customer' => 'tan', 'branch' => 'tagbilaran-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 36,
            'items' => [['SEA-SKYHAWK4TB', 2], ['WDC-PURPLE4TB', 2]], 'voucher' => 'STORAGE1000'],
        ['customer' => 'lim', 'branch' => 'cebu-city', 'status' => OrderStatus::Cancelled, 'payment' => PaymentStatus::Failed, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 33,
            'items' => [['PSU-12V10A', 2]], 'delivery_fee' => 400, 'notes' => 'Payment declined at the counter; customer did not return.'],
        ['customer' => 'abadilla', 'branch' => 'general-santos-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 29,
            'items' => [['EZV-C6N', 4]]],
        ['customer' => 'reyes', 'branch' => 'iloilo-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 25,
            'items' => [['DAH-NVR2104HSP', 1], ['DAH-HFW1430S1', 4]], 'voucher' => 'WELCOME10'],

        // --- The current month ------------------------------------------------
        ['customer' => 'macaraeg', 'branch' => 'davao-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 19,
            'items' => [['HIK-2CD2086G2', 3]], 'voucher' => 'CAMERA500'],
        ['customer' => 'ongkiko', 'branch' => 'quezon-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 17,
            'items' => [['HIK-KIS603P', 1], ['ZKT-AL280', 2]], 'delivery_fee' => 650],
        ['customer' => 'santos', 'branch' => 'davao-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 14,
            'items' => [['CBL-CAT6UTP', 2], ['CBL-RJ45100', 2]]],
        ['customer' => 'lim', 'branch' => 'cebu-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 12,
            'items' => [['ZKT-F18', 1], ['GEN-EXITBTN', 2]]],
        ['customer' => 'delacruz', 'branch' => 'quezon-city', 'status' => OrderStatus::Completed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 10,
            'items' => [['DAH-HDBW2841E', 2], ['DAH-HFW2449S', 2]], 'voucher' => 'CAMERA500'],
        ['customer' => 'tan', 'branch' => 'malaybalay-city', 'status' => OrderStatus::Ready, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 8,
            'items' => [['PAR-SP4000', 1], ['PAR-NV5', 4], ['PAR-K10V', 1]]],
        ['customer' => 'abadilla', 'branch' => 'general-santos-city', 'status' => OrderStatus::Ready, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 6,
            'items' => [['DAH-HACB1A21', 10], ['CBL-BNC20', 3]], 'voucher' => 'BULKCAM15', 'notes' => 'Please prepare by Friday morning — collection before 10am.'],
        ['customer' => 'reyes', 'branch' => 'davao-city', 'status' => OrderStatus::Preparing, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 5,
            'items' => [['BDL-HOMEWIFI', 1]]],
        ['customer' => 'macaraeg', 'branch' => 'iloilo-city', 'status' => OrderStatus::Preparing, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 4,
            'items' => [['DAH-SD49225XA', 1]], 'delivery_fee' => 1200, 'notes' => 'Ground floor delivery. Contact the property administrator on arrival.'],
        ['customer' => 'ongkiko', 'branch' => 'cebu-city', 'status' => OrderStatus::Confirmed, 'payment' => PaymentStatus::Paid, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 3,
            'items' => [['REO-RLC823A', 1], ['PSU-SURGE4', 1]]],
        ['customer' => 'santos', 'branch' => 'davao-city', 'status' => OrderStatus::Confirmed, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 2,
            'items' => [['GEN-SIREN30W', 2], ['GEN-PANICBTN', 1]]],
        ['customer' => 'lim', 'branch' => 'tagbilaran-city', 'status' => OrderStatus::Pending, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 1,
            'items' => [['UNV-3614LE', 2], ['CBL-CAT6UTP', 1]]],
        ['customer' => 'delacruz', 'branch' => 'quezon-city', 'status' => OrderStatus::Pending, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Delivery, 'days_ago' => 1,
            'items' => [['DAH-NVR5232', 1], ['SEA-SKYHAWK8TB', 1]], 'delivery_fee' => 750],
        ['customer' => 'reyes', 'branch' => 'davao-city', 'status' => OrderStatus::Pending, 'payment' => PaymentStatus::Pending, 'fulfillment' => FulfillmentType::Pickup, 'days_ago' => 0,
            'items' => [['HIK-7204HGHIK1', 1], ['HIK-2CE16D0T', 4]], 'voucher' => 'WELCOME10'],
    ];

    /**
     * Enquiries from the contact page and the branch directory. A `handled_days`
     * of null leaves the enquiry in the inbox.
     *
     * @var list<array{name: string, email: string, phone: string, branch: ?string, days_ago: int, handled_days: ?int, message: string}>
     */
    private const INQUIRIES = [
        [
            'name' => 'Fr. Antonio Villamor', 'email' => 'parish.office@example.ph', 'phone' => '9171112233',
            'branch' => 'tagbilaran-city', 'days_ago' => 27, 'handled_days' => 26,
            'message' => 'Good day. Our parish hall has four old analogue cameras that have stopped recording — the DVR shows a blue screen on every channel. Could someone look at it, and could you also quote a replacement system? We would need it before the fiesta season.',
        ],
        [
            'name' => 'Cherry Ann Bautista', 'email' => 'cherryann.bautista@example.ph', 'phone' => '9182223344',
            'branch' => 'davao-city', 'days_ago' => 21, 'handled_days' => 20,
            'message' => 'Hi, is the DS-7108NI-Q1/8P available in Davao? I need eight channels for our cooperative office. Also please confirm if you accept purchase orders from a cooperative.',
        ],
        [
            'name' => 'Engr. Rolando Ceniza', 'email' => 'r.ceniza@example.ph', 'phone' => '9273334455',
            'branch' => 'cebu-city', 'days_ago' => 16, 'handled_days' => 14,
            'message' => 'We are fitting out a new site office and need twelve IP cameras, a sixteen-channel recorder and the cabling. Could you prepare a quotation with bulk pricing on the cable? Delivery and installation to Mandaue.',
        ],
        [
            'name' => 'Sheila Marie Ompoc', 'email' => 'sheila.ompoc@example.ph', 'phone' => '9954445566',
            'branch' => null, 'days_ago' => 12, 'handled_days' => 11,
            'message' => 'Do you have a branch anywhere in Northern Mindanao? I am in Cagayan de Oro and the nearest one on your list is Malaybalay. Do you deliver and install, and is there a minimum order?',
        ],
        [
            'name' => 'Jomar Estrella', 'email' => 'jomar.estrella@example.ph', 'phone' => '9065556677',
            'branch' => 'general-santos-city', 'days_ago' => 9, 'handled_days' => 8,
            'message' => 'Two of my cameras drop out every night at around eight and come back in the morning. I already replaced the power supply and it did not help. How much is a service call, and would you need to take anything away?',
        ],
        [
            'name' => 'Maricel Alonzo', 'email' => 'maricel.alonzo@example.ph', 'phone' => '9176667788',
            'branch' => 'iloilo-city', 'days_ago' => 6, 'handled_days' => null,
            'message' => 'Requesting a quotation for 20 dome cameras and 3 eight-channel recorders, delivered to our school in Oton. We will be paying by cheque against a purchase order — please advise on your terms.',
        ],
        [
            'name' => 'Dr. Nathaniel Gorospe', 'email' => 'clinic.gorospe@example.ph', 'phone' => '9187778899',
            'branch' => 'quezon-city', 'days_ago' => 4, 'handled_days' => null,
            'message' => 'I need cameras for a small clinic — the waiting area, the corridor and the dispensary, and the footage must be clear enough to identify someone. Would you recommend colour night vision for this? Budget is around 25,000.',
        ],
        [
            'name' => 'Kristine Joy Panganiban', 'email' => 'kj.panganiban@example.ph', 'phone' => '9278889900',
            'branch' => 'davao-city', 'days_ago' => 3, 'handled_days' => null,
            'message' => 'Is the SkyHawk 8TB drive in stock in Davao? Your site shows only two. I need four and can wait a few days if you are expecting a delivery.',
        ],
        [
            'name' => 'Arnel Bacaltos', 'email' => 'arnel.bacaltos@example.ph', 'phone' => '9179990011',
            'branch' => 'malaybalay-city', 'days_ago' => 2, 'handled_days' => null,
            'message' => 'Our recorder keeps reporting that the disk is full and has stopped overwriting. Do you stock a 4TB surveillance drive, and can I collect it myself or does a technician have to fit it?',
        ],
        [
            'name' => 'Lourdes Fernandez', 'email' => 'lourdes.fernandez@example.ph', 'phone' => '9180001122',
            'branch' => null, 'days_ago' => 1, 'handled_days' => null,
            'message' => 'I saw the Sales Associate vacancy for Davao. Is it still open, and where should I send my application? I have two years of experience at a computer shop.',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $customers = $this->seedCustomers();
            $branches = Branch::query()->pluck('id', 'slug');
            $products = Product::query()->get()->keyBy('sku');
            $vouchers = Voucher::query()->get()->keyBy('code');
            $sequences = [];

            foreach (self::ORDERS as $row) {
                $this->seedOrder($row, $customers, $branches, $products, $vouchers, $sequences);
            }

            $this->seedInquiries($branches);
        });
    }

    /**
     * Sign-ins for the customers who have one, so the account pages are not
     * empty. Passwords come from the environment, never from a literal here.
     *
     * @return Collection<string, User>
     */
    private function seedCustomers(): Collection
    {
        $password = Hash::make(config('sentrix.seed_password'));

        return collect(self::CUSTOMERS)
            ->filter(fn (array $customer): bool => $customer['account'])
            ->map(fn (array $customer): User => tap(
                User::query()->updateOrCreate(
                    ['email' => $customer['email']],
                    [
                        'name' => $customer['first'].' '.$customer['last'],
                        'password' => $password,
                        'phone' => $customer['phone'],
                        'is_active' => true,
                    ]
                ),
                fn (User $user) => $user->syncRoles(['customer'])
            ));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, User>  $customers
     * @param  Collection<string, int>  $branches
     * @param  Collection<string, Product>  $products
     * @param  Collection<string, Voucher>  $vouchers
     * @param  array<string, int>  $sequences
     */
    private function seedOrder(
        array $row,
        Collection $customers,
        Collection $branches,
        Collection $products,
        Collection $vouchers,
        array &$sequences,
    ): void {
        $customer = self::CUSTOMERS[$row['customer']];
        $placedAt = now()->subDays($row['days_ago'])->setTime(9 + ($row['days_ago'] % 8), 15);

        $lines = $this->lines($row['items'], $products);
        $subtotal = $lines->sum('line_total');
        $voucher = isset($row['voucher']) ? $vouchers->get($row['voucher']) : null;
        $discount = $voucher === null ? 0.0 : $this->discountFor($voucher, $subtotal, $lines->sum('quantity'));
        $deliveryFee = $row['delivery_fee'] ?? 0;

        $order = Order::query()->updateOrCreate(
            ['order_number' => $this->orderNumber($placedAt, $sequences)],
            [
                'user_id' => $customers->get($row['customer'])?->id,
                'branch_id' => $branches[$row['branch']],
                'voucher_id' => $voucher?->id,
                'status' => $row['status'],
                'payment_status' => $row['payment'],
                'fulfillment_type' => $row['fulfillment'],
                'customer_first_name' => $customer['first'],
                'customer_last_name' => $customer['last'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'delivery_address' => $row['fulfillment'] === FulfillmentType::Delivery
                    ? $this->deliveryAddress($row['branch'])
                    : null,
                'notes' => $row['notes'] ?? null,
                'scheduled_for' => $placedAt->copy()->addDays(2)->setTime(10, 0),
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'delivery_fee' => $deliveryFee,
                'grand_total' => round($subtotal - $discount + $deliveryFee, 2),
                'paid_at' => $row['payment'] === PaymentStatus::Paid ? $placedAt->copy()->addHours(6) : null,
                'archived_at' => ($row['archived'] ?? false) ? $placedAt->copy()->addDays(30) : null,
            ]
        );

        $this->backdate($order, $placedAt);

        $order->items()->delete();

        foreach ($lines as $line) {
            OrderItem::query()->create(['order_id' => $order->id] + $line);
        }

        if ($voucher !== null && $discount > 0) {
            VoucherRedemption::query()->updateOrCreate(
                ['voucher_id' => $voucher->id, 'order_id' => $order->id],
                ['user_id' => $order->user_id, 'amount' => $discount]
            );
        }
    }

    /**
     * Snapshot the ordered products onto order lines, the way checkout does:
     * name, SKU and price are copied so a later catalogue edit cannot rewrite
     * what a customer was charged.
     *
     * @param  list<array{0: string, 1: int}>  $items
     * @param  Collection<string, Product>  $products
     * @return Collection<int, array<string, mixed>>
     */
    private function lines(array $items, Collection $products): Collection
    {
        return collect($items)
            ->map(function (array $item) use ($products): ?array {
                if (($product = $products->get($item[0])) === null) {
                    return null;
                }

                $unitPrice = (float) $product->price;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $item[1],
                    'line_total' => round($unitPrice * $item[1], 2),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Apply the voucher's own rule rather than a written-down figure, so the
     * discount on an order always matches the voucher attached to it.
     */
    private function discountFor(Voucher $voucher, float $subtotal, int $quantity): float
    {
        if ($voucher->type->requiresMinimumQuantity() && $quantity < (int) $voucher->min_quantity) {
            return 0.0;
        }

        if ($voucher->min_subtotal !== null && $subtotal < (float) $voucher->min_subtotal) {
            return 0.0;
        }

        $discount = $voucher->type->isPercentage()
            ? $subtotal * ((float) $voucher->value / 100)
            : (float) $voucher->value;

        return round(min($discount, $subtotal), 2);
    }

    /**
     * The KMP-ymd-0001 shape OrderNumberGenerator produces, numbered within the
     * day so the seeded history is indistinguishable from real trading.
     *
     * @param  array<string, int>  $sequences
     */
    private function orderNumber(Carbon $placedAt, array &$sequences): string
    {
        $datePart = $placedAt->format('ymd');
        $sequences[$datePart] = ($sequences[$datePart] ?? 0) + 1;

        return sprintf('%s-%s-%04d', config('sentrix.order_number_prefix'), $datePart, $sequences[$datePart]);
    }

    /**
     * Timestamps are guarded, so the placement date is written after the insert.
     * Without this every order would read as having been placed today and the
     * revenue and status figures would say nothing.
     */
    private function backdate(Order $order, Carbon $placedAt): void
    {
        $order->timestamps = false;
        $order->forceFill(['created_at' => $placedAt, 'updated_at' => $placedAt])->save();
        $order->timestamps = true;
    }

    /**
     * A delivery address in the branch's own city, so a delivery order does not
     * read as being shipped across the country.
     */
    private function deliveryAddress(string $branchSlug): string
    {
        return match ($branchSlug) {
            'cebu-city' => 'Unit 4B, Banilad Town Centre, Banilad, Cebu City, Cebu 6000',
            'iloilo-city' => '18 Benigno Aquino Avenue, Mandurriao, Iloilo City, Iloilo 5000',
            'general-santos-city' => 'Purok Malakas, Barangay Lagao, General Santos City, South Cotabato 9500',
            'quezon-city' => '4th Floor, 1201 Quezon Avenue, Barangay Paligsahan, Quezon City, Metro Manila 1103',
            default => '27 Bolton Extension, Barangay 20-B, Davao City, Davao del Sur 8000',
        };
    }

    /**
     * @param  Collection<string, int>  $branches
     */
    private function seedInquiries(Collection $branches): void
    {
        foreach (self::INQUIRIES as $row) {
            $receivedAt = now()->subDays($row['days_ago'])->setTime(11, 30);

            $inquiry = Inquiry::query()->updateOrCreate(
                ['email' => $row['email'], 'message' => $row['message']],
                [
                    'branch_id' => $row['branch'] === null ? null : ($branches[$row['branch']] ?? null),
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'handled_at' => $row['handled_days'] === null
                        ? null
                        : now()->subDays($row['handled_days'])->setTime(14, 0),
                ]
            );

            $inquiry->timestamps = false;
            $inquiry->forceFill(['created_at' => $receivedAt, 'updated_at' => $receivedAt])->save();
            $inquiry->timestamps = true;
        }
    }
}
