<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number', 'user_id', 'branch_id', 'voucher_id',
    'status', 'payment_status', 'fulfillment_type',
    'customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone',
    'delivery_address', 'notes', 'scheduled_for',
    'subtotal', 'discount_total', 'delivery_fee', 'grand_total',
    'paid_at', 'archived_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_type' => FulfillmentType::class,
            'scheduled_for' => 'datetime',
            'paid_at' => 'datetime',
            'archived_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function redemption(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function customerName(): string
    {
        return trim($this->customer_first_name.' '.$this->customer_last_name);
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }
}
