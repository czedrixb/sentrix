<?php

namespace App\Models;

use Database\Factories\PostImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['post_id', 'path', 'caption', 'position'])]
class PostImage extends Model
{
    /** @use HasFactory<PostImageFactory> */
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Absolute URL for the SPA, which cannot resolve relative storage paths.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
