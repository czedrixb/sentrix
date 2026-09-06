<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::uuid()->toString(),
            'user_id' => null,
            'branch_id' => Branch::factory(),
            'voucher_id' => null,
            'fulfillment_type' => null,
            'expires_at' => now()->addDays(30),
        ];
    }
}
