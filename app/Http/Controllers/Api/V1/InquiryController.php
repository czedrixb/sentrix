<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;

class InquiryController extends Controller
{
    /**
     * Record a contact or branch enquiry.
     *
     * A null branch_id means a general enquiry; any branch, including one added
     * after launch, works without a new route.
     */
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        Inquiry::query()->create($request->validated());

        return response()->json([
            'message' => 'Thanks for getting in touch. We will reply shortly.',
        ], 201);
    }
}
