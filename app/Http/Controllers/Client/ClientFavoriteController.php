<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientFavorite;
use App\Models\MariachiListing;
use App\Services\MariachiProfileStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientFavoriteController extends Controller
{
    public function store(Request $request, string $slug, MariachiProfileStatsService $statsService): RedirectResponse
    {
        $profile = MariachiListing::query()->where('slug', $slug)->published()->firstOrFail();

        $favorite = ClientFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'mariachi_profile_id' => $profile->mariachi_profile_id,
            'mariachi_listing_id' => $profile->id,
        ]);

        if ($favorite->wasRecentlyCreated && $profile->mariachiProfile) {
            $statsService->incrementFavorites($profile->mariachiProfile);
        }

        return back()->with('status', 'Anuncio guardado en favoritos.');
    }

    public function destroy(Request $request, string $slug, MariachiProfileStatsService $statsService): RedirectResponse
    {
        $profile = MariachiListing::query()->where('slug', $slug)->firstOrFail();

        $deleted = ClientFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('mariachi_listing_id', $profile->id)
            ->delete();

        if ($deleted > 0 && $profile->mariachiProfile) {
            $statsService->decrementFavorites($profile->mariachiProfile);
        }

        return back()->with('status', 'Anuncio eliminado de favoritos.');
    }
}
