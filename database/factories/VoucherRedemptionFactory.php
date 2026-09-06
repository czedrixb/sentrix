<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherRedemption>
 */
class VoucherRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'order_id' => Order::factory(),
            'user_id' => null,
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
