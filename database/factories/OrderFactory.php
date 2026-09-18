<?php

namespace Database\Factories;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 50000);

        return [
            'order_number' => 'SNX-'.fake()->unique()->numerify('########'),
            'user_id' => null,
            'branch_id' => Branch::factory(),
            'voucher_id' => null,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'fulfillment_type' => FulfillmentType::Pickup,
            'customer_first_name' => fake()->firstName(),
            'customer_last_name' => fake()->lastName(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '9'.fake()->numerify('#########'),
            'delivery_address' => null,
            'notes' => null,
            'scheduled_for' => now()->addDays(2)->setTime(10, 0),
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'delivery_fee' => 0,
            'grand_total' => $subtotal,
            'paid_at' => null,
            'archived_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Completed,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['archived_at' => now()]);
    }
}
