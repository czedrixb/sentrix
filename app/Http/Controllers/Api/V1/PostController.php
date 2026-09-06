<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->published()
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn (Builder $search) => $search
                    ->where('title', 'like', $term)
                    ->orWhere('author', 'like', $term)
                    ->orWhere('excerpt', 'like', $term));
            })
            ->latest('published_at')
            ->paginate($request->integer('per_page', 9))
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function show(Post $post): PostResource
    {
        abort_if($post->published_at === null || $post->published_at->isFuture(), 404);

        return new PostResource($post->load('images'));
    }
}
