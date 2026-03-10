<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MariachiReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $statusFilter = (string) $request->query('status', 'all');

        $reviewsQuery = MariachiReview::query()
            ->forMariachiUser($user->id)
            ->with([
                'clientUser:id,name,first_name,last_name',
                'photos',
                'conversation:id,event_date,event_city',
                'mariachiListing:id,mariachi_profile_id,title',
            ]);

        if ($statusFilter !== 'all' && in_array($statusFilter, MariachiReview::MODERATION_STATUSES, true)) {
            $reviewsQuery->where('moderation_status', $statusFilter);
        }

        $reviews = $reviewsQuery
            ->latest('created_at')
            ->get();

        $statusTotals = MariachiReview::query()
            ->forMariachiUser($user->id)
            ->selectRaw('moderation_status, count(*) as total')
            ->groupBy('moderation_status')
            ->pluck('total', 'moderation_status');

        $approvedVisibleQuery = MariachiReview::query()
            ->forMariachiUser($user->id)
            ->publicVisible();

        $totalApproved = (clone $approvedVisibleQuery)->count();
        $averageRating = round((float) ((clone $approvedVisibleQuery)->avg('rating') ?? 0), 1);

        $distribution = MariachiReview::query()
            ->forMariachiUser($user->id)
            ->publicVisible()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingDistribution = $this->normalizeRatingDistribution($distribution);
        $totalReviews = (int) $statusTotals->sum();

        $baseReviewsQuery = MariachiReview::query()->forMariachiUser($user->id);
        $thisWeekReviews = (clone $baseReviewsQuery)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $lastWeekReviews = (clone $baseReviewsQuery)
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->count();

        $weeklyGrowthPercentage = 0.0;
        if ($lastWeekReviews > 0) {
            $weeklyGrowthPercentage = round((($thisWeekReviews - $lastWeekReviews) / $lastWeekReviews) * 100, 1);
        } elseif ($thisWeekReviews > 0) {
            $weeklyGrowthPercentage = 100.0;
        }

        $positiveReviews = (int) ($ratingDistribution[5] ?? 0) + (int) ($ratingDistribution[4] ?? 0);
        $positivePercentage = $totalApproved > 0
            ? (int) round(($positiveReviews / $totalApproved) * 100)
            : 0;

        return view('content.mariachi.reviews-index', [
            'reviews' => $reviews,
            'statusFilter' => $statusFilter,
            'statusTotals' => $statusTotals,
            'totalApproved' => $totalApproved,
            'averageRating' => $averageRating,
            'ratingDistribution' => $ratingDistribution,
            'totalReviews' => $totalReviews,
            'thisWeekReviews' => $thisWeekReviews,
            'weeklyGrowthPercentage' => $weeklyGrowthPercentage,
            'positivePercentage' => $positivePercentage,
        ]);
    }

    public function reply(Request $request, MariachiReview $review): RedirectResponse
    {
        $ownerId = $review->mariachiListing?->mariachiProfile?->user_id
            ?? $review->mariachiProfile?->user_id;
        abort_unless($ownerId === $request->user()->id, 403);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        $review->update([
            'mariachi_reply' => $validated['reply'],
            'mariachi_replied_at' => now(),
            'mariachi_reply_visible' => true,
        ]);

        return back()->with('status', 'Respuesta publicada en la resena.');
    }

    public function report(Request $request, MariachiReview $review): RedirectResponse
    {
        $ownerId = $review->mariachiListing?->mariachiProfile?->user_id
            ?? $review->mariachiProfile?->user_id;
        abort_unless($ownerId === $request->user()->id, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $review->increment('reports_count');

        $review->update([
            'moderation_status' => MariachiReview::STATUS_REPORTED,
            'is_visible' => false,
            'latest_report_reason' => $validated['reason'],
            'reported_at' => now(),
            'reported_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Resena reportada al equipo de moderacion.');
    }

    /**
     * @param  Collection<int, int|string>  $distribution
     * @return array<int, int>
     */
    private function normalizeRatingDistribution(Collection $distribution): array
    {
        $normalized = [];

        foreach ([5, 4, 3, 2, 1] as $rating) {
            $normalized[$rating] = (int) ($distribution[$rating] ?? 0);
        }

        return $normalized;
    }
}
