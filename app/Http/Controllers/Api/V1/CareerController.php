<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CareerResource;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CareerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $careers = Career::query()
            ->open()
            ->with('branch')
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return CareerResource::collection($careers);
    }

    public function show(Career $career): CareerResource
    {
        return new CareerResource($career->load('branch'));
    }
}
