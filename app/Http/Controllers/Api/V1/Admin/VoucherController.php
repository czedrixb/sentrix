<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VoucherType;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vouchers = Voucher::query()
            ->withCount('redemptions')
            ->when($request->filled('q'), fn ($query) => $query->where('code', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json($vouchers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $productIds = $validated['product_ids'] ?? [];
        unset($validated['product_ids']);

        $voucher = Voucher::query()->create($validated);
        $voucher->products()->sync($productIds);

        return response()->json(['data' => $voucher->load('products')], 201);
    }

    public function show(Voucher $voucher): JsonResponse
    {
        return response()->json(['data' => $voucher->load('products')->loadCount('redemptions')]);
    }

    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $this->validated($request, $voucher);

        if (array_key_exists('product_ids', $validated)) {
            $voucher->products()->sync($validated['product_ids'] ?? []);
            unset($validated['product_ids']);
        }

        $voucher->update($validated);

        return response()->json(['data' => $voucher->fresh('products')]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        $voucher->delete();

        return response()->json(['message' => 'Voucher deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Voucher $voucher = null): array
    {
        $required = $voucher === null ? 'required' : 'sometimes';

        return $request->validate([
            'code' => [$required, 'string', 'max:64', Rule::unique('vouchers', 'code')->ignore($voucher?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => [$required, Rule::enum(VoucherType::class)],
            'value' => [$required, 'numeric', 'min:0'],

            // Only meaningful for the minimum-quantity types, and required there.
            'min_quantity' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn (): bool => in_array(
                    $request->input('type'),
                    [VoucherType::PercentageMinQuantity->value, VoucherType::FixedMinQuantity->value],
                    true
                )),
            ],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],

            // Restricting a voucher to products is a real pivot, not a JSON list.
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);
    }
}
