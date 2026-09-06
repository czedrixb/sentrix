<?php

namespace App\Models;

use Database\Factories\BundleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['bundle_product_id', 'product_id', 'label', 'quantity', 'position'])]
class BundleItem extends Model
{
    /** @use HasFactory<BundleItemFactory> */
    use HasFactory;

    /**
     * The bundle this inclusion belongs to.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    /**
     * The catalogue product this inclusion points at, when it is linked.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
