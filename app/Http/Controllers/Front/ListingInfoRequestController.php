<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ListingInfoRequest;
use App\Models\MariachiListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListingInfoRequestController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $listing = MariachiListing::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'event_date' => ['required', 'date'],
            'event_city' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

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
