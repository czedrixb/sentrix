<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Branch management.
 *
 * This is the whole cost of opening a new location: create a row here. The
 * observer gives it a stock line for every product, the storefront picks it up
 * from the branches endpoint, enquiries can be addressed to it, and a manager
 * is assigned by setting branch_id on a user. No migration, no new role, no new
 * route, no code change anywhere.
 */
class BranchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return BranchResource::collection(
            Branch::query()
                ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
                ->orderBy('position')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $branch = Branch::query()->create($this->validated($request));

        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    public function show(Branch $branch): BranchResource
    {
        return new BranchResource($branch);
    }

    public function update(Request $request, Branch $branch): BranchResource
    {
        $branch->update($this->validated($request, $branch));

        return new BranchResource($branch->fresh());
    }

    /**
     * Branches are deactivated rather than deleted once they carry orders, so
     * order history keeps its branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        if ($branch->orders()->exists()) {
            $branch->update(['is_active' => false]);

            return response()->json([
                'message' => 'This branch has orders, so it was deactivated rather than deleted.',
            ]);
        }

        $branch->delete();

        return response()->json(['message' => 'Branch deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'name' => [$branch === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('branches', 'slug')->ignore($branch?->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'sales_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'map_embed' => ['nullable', 'string', 'max:5000'],
            'is_pickup_location' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
