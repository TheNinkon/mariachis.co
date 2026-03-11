<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MariachiListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminListingModerationController extends Controller
{
    public function index(Request $request): View
    {
        $reviewStatus = (string) $request->query('review_status', 'all');
        $city = trim((string) $request->query('city', ''));
        $search = trim((string) $request->query('search', ''));
        $reason = trim((string) $request->query('reason', ''));

        $listingsQuery = MariachiListing::query()
            ->with([
                'mariachiProfile:id,user_id,business_name,city_name',
                'mariachiProfile.user:id,name,first_name,last_name,email',
                'marketplaceCity:id,name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->withCount(['photos', 'videos', 'reviews', 'quoteConversations']);

        if ($reviewStatus !== 'all' && in_array($reviewStatus, MariachiListing::REVIEW_STATUSES, true)) {
            $listingsQuery->where('review_status', $reviewStatus);
        }

        if ($city !== '') {
            $listingsQuery->whereRaw('LOWER(city_name) = ?', [mb_strtolower($city)]);
        }

        if ($search !== '') {
            $term = '%'.$search.'%';

            $listingsQuery->where(function ($query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('city_name', 'like', $term)
                    ->orWhereHas('mariachiProfile', function ($profileQuery) use ($term): void {
                        $profileQuery->where('business_name', 'like', $term)
                            ->orWhere('responsible_name', 'like', $term);
                    })
                    ->orWhereHas('mariachiProfile.user', function ($userQuery) use ($term): void {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        if ($reason !== '') {
            $listingsQuery->where('rejection_reason', 'like', '%'.$reason.'%');
        }

        $listings = $listingsQuery
            ->orderByRaw("
                case review_status
                    when 'pending' then 0
                    when 'rejected' then 1
                    when 'draft' then 2
                    when 'approved' then 3
                    else 4
                end
            ")
            ->latest('submitted_for_review_at')
            ->latest('updated_at')
            ->paginate(18)
            ->withQueryString();

        $statusTotals = MariachiListing::query()
            ->selectRaw('review_status, count(*) as total')
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        $cities = MariachiListing::query()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->orderBy('city_name')
            ->distinct()
            ->pluck('city_name');

        return view('content.admin.listings-index', [
            'listings' => $listings,
            'reviewStatus' => $reviewStatus,
            'city' => $city,
            'search' => $search,
            'reason' => $reason,
            'cities' => $cities,
            'statuses' => MariachiListing::REVIEW_STATUSES,
            'statusTotals' => $statusTotals,
        ]);
    }

    public function show(MariachiListing $listing): View
    {
        $listing->load([
            'mariachiProfile.user:id,name,first_name,last_name,email,phone',
            'marketplaceCity:id,name',
            'photos',
            'videos',
            'serviceAreas.marketplaceZone:id,marketplace_city_id,name',
            'faqs',
            'eventTypes:id,name',
            'serviceTypes:id,name',
            'groupSizeOptions:id,name',
            'budgetRanges:id,name',
            'reviewedBy:id,name,first_name,last_name',
        ])->loadCount(['quoteConversations', 'reviews']);

        return view('content.admin.listings-show', [
            'listing' => $listing,
        ]);
    }

    public function moderate(Request $request, MariachiListing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $payload = [
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'submitted_for_review_at' => $listing->submitted_for_review_at ?? now(),
        ];

        if ($validated['action'] === 'approve') {
            $listing->update($payload + [
                'review_status' => MariachiListing::REVIEW_APPROVED,
                'rejection_reason' => null,
            ]);

            return redirect()
                ->route('admin.listings.show', $listing)
                ->with('status', 'Anuncio aprobado para publicación.');
        }

        $listing->update($payload + [
            'review_status' => MariachiListing::REVIEW_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()
            ->route('admin.listings.show', $listing)
            ->with('status', 'Anuncio rechazado y devuelto al mariachi.');
    }
}
