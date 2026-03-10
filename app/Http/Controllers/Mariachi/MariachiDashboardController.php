<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\ClientFavorite;
use App\Models\ClientRecentView;
use App\Models\MariachiReview;
use App\Models\QuoteConversation;
use Illuminate\View\View;

class MariachiDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $profile = $user->mariachiProfile?->loadMissing('stat');
        $listingLimit = $profile?->listingLimit() ?? 1;

        $listings = $profile
            ? $profile->listings()
                ->withCount([
                    'recentViews as views_count',
                    'favoritedByUsers as favorites_count',
                    'quoteConversations as quotes_count',
                    'quoteConversations as open_quotes_count' => function ($query): void {
                        $query->whereIn('status', [
                            QuoteConversation::STATUS_NEW,
                            QuoteConversation::STATUS_IN_PROGRESS,
                        ]);
                    },
                    'reviews as approved_reviews_count' => function ($query): void {
                        $query->where('moderation_status', MariachiReview::STATUS_APPROVED)
                            ->where('is_visible', true);
                    },
                ])
                ->withAvg([
                    'reviews as approved_rating_avg' => function ($query): void {
                        $query->where('moderation_status', MariachiReview::STATUS_APPROVED)
                            ->where('is_visible', true);
                    },
                ], 'rating')
                ->withMax('quoteConversations as last_quote_at', 'last_message_at')
                ->latest('updated_at')
                ->get()
            : collect();

        $listingTotals = $profile
            ? $profile->listings()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $conversationBaseQuery = QuoteConversation::query()
            ->forMariachiUser($user->id);

        $quoteTotals = (clone $conversationBaseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalConversations = (clone $conversationBaseQuery)->count();
        $repliedConversations = (clone $conversationBaseQuery)
            ->whereHas('messages', function ($query) use ($user): void {
                $query->where('sender_user_id', $user->id);
            })
            ->count();

        $pendingFirstReply = max(0, $totalConversations - $repliedConversations);
        $responseRate = $totalConversations > 0
            ? (int) round(($repliedConversations / $totalConversations) * 100)
            : 0;

        $listingIds = $listings->pluck('id')->filter()->values();
        $viewsTotal = (int) $listings->sum('views_count');
        $favoritesTotal = (int) $listings->sum('favorites_count');
        $quotesTotal = (int) $listings->sum('quotes_count');
        $openQuotesTotal = (int) $listings->sum('open_quotes_count');
        $approvedReviewsTotal = (int) $listings->sum('approved_reviews_count');

        $weightedRatingSum = $listings->sum(function ($listing): float {
            return ((float) ($listing->approved_rating_avg ?? 0)) * ((int) ($listing->approved_reviews_count ?? 0));
        });
        $approvedRatingAvg = $approvedReviewsTotal > 0
            ? round($weightedRatingSum / $approvedReviewsTotal, 2)
            : 0.0;

        $periodStart = now()->subDays(30);
        $newListings30d = $profile
            ? $profile->listings()->where('created_at', '>=', $periodStart)->count()
            : 0;
        $quotes30d = (clone $conversationBaseQuery)
            ->where('created_at', '>=', $periodStart)
            ->count();
        $views30d = $listingIds->isNotEmpty()
            ? ClientRecentView::query()
                ->whereIn('mariachi_listing_id', $listingIds)
                ->where('last_viewed_at', '>=', $periodStart)
                ->count()
            : 0;
        $favorites30d = $listingIds->isNotEmpty()
            ? ClientFavorite::query()
                ->whereIn('mariachi_listing_id', $listingIds)
                ->where('created_at', '>=', $periodStart)
                ->count()
            : 0;

        return view('content.dashboard.mariachi', [
            'user' => $user,
            'profile' => $profile,
            'stats' => $profile?->stat,
            'listings' => $listings,
            'quoteTotals' => $quoteTotals,
            'listingTotals' => $listingTotals,
            'listingLimit' => $listingLimit,
            'viewsTotal' => $viewsTotal,
            'favoritesTotal' => $favoritesTotal,
            'quotesTotal' => $quotesTotal,
            'openQuotesTotal' => $openQuotesTotal,
            'approvedReviewsTotal' => $approvedReviewsTotal,
            'approvedRatingAvg' => $approvedRatingAvg,
            'totalConversations' => $totalConversations,
            'repliedConversations' => $repliedConversations,
            'pendingFirstReply' => $pendingFirstReply,
            'responseRate' => $responseRate,
            'newListings30d' => (int) $newListings30d,
            'quotes30d' => (int) $quotes30d,
            'views30d' => (int) $views30d,
            'favorites30d' => (int) $favorites30d,
        ]);
    }
}
