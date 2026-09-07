<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout details.
 *
 * Note what is absent: no subtotal, no discount, no total. The previous system
 * accepted all three from hidden inputs and charged whatever arrived.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_first_name' => ['required', 'string', 'max:100'],
            'customer_last_name' => ['required', 'string', 'max:100'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^9\d{9}$/'],
            'fulfillment_type' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['nullable', 'required_if:fulfillment_type,delivery', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'scheduled_for' => [
                'required',
                'date',
                'after:now',
                'before_or_equal:'.now()->addDays((int) config('sentrix.scheduling.max_days_ahead'))->toDateTimeString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a valid Philippine mobile number, for example 9171234567.',
            'scheduled_for.after' => 'Choose a pick-up or delivery time in the future.',
            'delivery_address.required_if' => 'A delivery address is required for delivery orders.',
        ];
    }
}
