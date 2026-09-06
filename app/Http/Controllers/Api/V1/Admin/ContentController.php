<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CareerResource;
use App\Http\Resources\GalleryImageResource;
use App\Http\Resources\PostResource;
use App\Models\Banner;
use App\Models\Career;
use App\Models\GalleryImage;
use App\Models\Post;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CMS content: news posts, careers, banners and the gallery.
 */
class ContentController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    // ----------------------------------------------------------------- Posts

    public function posts(Request $request): AnonymousResourceCollection
    {
        return PostResource::collection(
            Post::query()->latest()->paginate($request->integer('per_page', 20))->withQueryString()
        );
    }

    public function storePost(Request $request): JsonResponse
    {
        $post = Post::query()->create($this->postAttributes($request));

        $this->attachPostImages($request, $post);

        return (new PostResource($post->load('images')))->response()->setStatusCode(201);
    }

    public function showPost(Post $post): PostResource
    {
        return new PostResource($post->load('images'));
    }

    public function updatePost(Request $request, Post $post): PostResource
    {
        $post->update($this->postAttributes($request, $post));

        $this->attachPostImages($request, $post);

        return new PostResource($post->fresh('images'));
    }

    public function destroyPost(Post $post): JsonResponse
    {
        $this->media->delete($post->thumbnail_path);

        foreach ($post->images as $image) {
            $this->media->delete($image->path);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    // --------------------------------------------------------------- Careers

    public function careers(Request $request): AnonymousResourceCollection
    {
        return CareerResource::collection(
            Career::query()->with('branch')->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function storeCareer(Request $request): JsonResponse
    {
        $career = Career::query()->create($this->careerAttributes($request));

        return (new CareerResource($career))->response()->setStatusCode(201);
    }

    public function updateCareer(Request $request, Career $career): CareerResource
    {
        $career->update($this->careerAttributes($request, $career));

        return new CareerResource($career->fresh('branch'));
    }

    public function destroyCareer(Career $career): JsonResponse
    {
        $career->delete();

        return response()->json(['message' => 'Career deleted.']);
    }

    // --------------------------------------------------------------- Banners

    public function banners(): AnonymousResourceCollection
    {
        return BannerResource::collection(Banner::query()->orderBy('position')->get());
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'headline' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $banner = Banner::query()->create([
            ...collect($validated)->except(['image', 'mobile_image'])->all(),
            'image_path' => $this->media->store($request->file('image'), 'banners'),
            'mobile_image_path' => $request->hasFile('mobile_image')
                ? $this->media->store($request->file('mobile_image'), 'banners')
                : null,
        ]);

        return (new BannerResource($banner))->response()->setStatusCode(201);
    }

    public function updateBanner(Request $request, Banner $banner): BannerResource
    {
        $validated = $request->validate([
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'headline' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->media->replace($banner->image_path, $request->file('image'), 'banners');
        }

        unset($validated['image']);

        $banner->update($validated);

        return new BannerResource($banner->fresh());
    }

    public function destroyBanner(Banner $banner): JsonResponse
    {
        $this->media->delete($banner->image_path);
        $this->media->delete($banner->mobile_image_path);
        $banner->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    // --------------------------------------------------------------- Gallery

    public function gallery(): AnonymousResourceCollection
    {
        return GalleryImageResource::collection(GalleryImage::query()->orderBy('position')->get());
    }

    public function storeGalleryImages(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $position = (int) GalleryImage::query()->max('position');
        $created = [];

        foreach ($request->file('images') as $file) {
            $created[] = GalleryImage::query()->create([
                'path' => $this->media->store($file, 'gallery'),
                'caption' => $request->input('caption'),
                'position' => ++$position,
            ]);
        }

        return GalleryImageResource::collection(collect($created));
    }

    /**
     * Removes the file as well as the row -- the previous implementation read
     * the wrong column name, so every gallery file was orphaned on disk.
     */
    public function destroyGalleryImage(GalleryImage $image): JsonResponse
    {
        $this->media->delete($image->path);
        $image->delete();

        return response()->json(['message' => 'Image deleted.']);
    }

    // ------------------------------------------------------------- Internals

    /**
     * @return array<string, mixed>
     */
    private function postAttributes(Request $request, ?Post $post = null): array
    {
        $validated = $request->validate([
            'title' => [$post === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'author' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $this->media->replace(
                $post?->thumbnail_path,
                $request->file('thumbnail'),
                'posts'
            );
        }

        unset($validated['thumbnail'], $validated['images']);

        if (isset($validated['title'])) {
            $validated['slug'] ??= Str::slug($validated['title']);
        }

        return $validated;
    }

    /**
     * Guarded against a missing file set, which crashed the previous version.
     */
    private function attachPostImages(Request $request, Post $post): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $position = (int) $post->images()->max('position');

        foreach ($request->file('images') as $file) {
            $post->images()->create([
                'path' => $this->media->store($file, 'posts'),
                'position' => ++$position,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function careerAttributes(Request $request, ?Career $career = null): array
    {
        $validated = $request->validate([
            'title' => [$career === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('careers', 'slug')->ignore($career?->id)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'vacancies' => ['nullable', 'integer', 'min:1'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'apply_email' => ['nullable', 'email', 'max:255'],
            'is_open' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] ??= Str::slug($validated['title']);
        }

        return $validated;
    }
}
