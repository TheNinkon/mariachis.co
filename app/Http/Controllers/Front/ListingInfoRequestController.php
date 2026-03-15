<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingInfoRequest;
use App\Models\ListingInfoRequest;
use App\Models\MariachiListing;
use Illuminate\Http\JsonResponse;

class ListingInfoRequestController extends Controller
{
    public function store(StoreListingInfoRequest $request, string $slug): JsonResponse
    {
        $listing = MariachiListing::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validated();

        ListingInfoRequest::query()->create([
            'mariachi_profile_id' => $listing->mariachi_profile_id,
            'mariachi_listing_id' => $listing->id,
            'client_user_id' => $request->user()?->id,
            'status' => ListingInfoRequest::STATUS_NEW,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'event_date' => $validated['event_date'],
            'event_city' => $validated['event_city'] ?: null,
            'message' => $validated['message'],
            'source' => 'public_listing',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Tu mensaje fue enviado. El mariachi podrá responderte con más información.',
        ], 201);
    }
}
