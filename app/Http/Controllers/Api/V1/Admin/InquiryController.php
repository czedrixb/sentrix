<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * One enquiry list, filterable by branch.
 *
 * This single controller replaces eight near-identical tables, models,
 * controllers and admin screens in the previous system -- and it covers any
 * branch added in future without a line of new code.
 */
class InquiryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Inquiry::class);

        $user = $request->user();

        $inquiries = Inquiry::query()
            ->with('branch')
            // Branch staff see their own branch plus general enquiries.
            ->when($user->branch_id, fn (Builder $query) => $query->where(
                fn (Builder $scope) => $scope->where('branch_id', $user->branch_id)->orWhereNull('branch_id')
            ))
            ->when($request->filled('branch_id'), fn (Builder $q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->boolean('general_only'), fn (Builder $q) => $q->whereNull('branch_id'))
            ->when($request->boolean('unhandled_only'), fn (Builder $q) => $q->unhandled())
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';

                $query->where(fn (Builder $s) => $s
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('message', 'like', $term));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return InquiryResource::collection($inquiries);
    }

    public function show(Inquiry $inquiry): InquiryResource
    {
        $this->authorize('view', $inquiry);

        return new InquiryResource($inquiry->load('branch'));
    }

    /**
     * Mark an enquiry as dealt with, or reopen it.
     */
    public function toggleHandled(Inquiry $inquiry): InquiryResource
    {
        $this->authorize('update', $inquiry);

        $inquiry->update(['handled_at' => $inquiry->handled_at === null ? now() : null]);

        return new InquiryResource($inquiry->fresh('branch'));
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $this->authorize('delete', $inquiry);

        $inquiry->delete();

        return response()->json(['message' => 'Enquiry deleted.']);
    }
}
