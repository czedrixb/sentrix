<?php

namespace App\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id', 'title', 'slug', 'vacancies', 'employment_type',
    'description', 'apply_email', 'is_open',
])]
class Career extends Model
{
    /** @use HasFactory<CareerFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<Career>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('is_open', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
