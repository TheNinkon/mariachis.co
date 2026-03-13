<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingPayment;
use App\Models\MariachiListing;
use App\Models\Plan;
use App\Services\PlanAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminListingModerationController extends Controller
{
    public function __construct(
        private readonly PlanAssignmentService $planAssignmentService
    ) {
    }

    public function index(Request $request): View
    {
        $reviewStatus = (string) $request->query('review_status', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $city = trim((string) $request->query('city', ''));
        $search = trim((string) $request->query('search', ''));
        $reason = trim((string) $request->query('reason', ''));

        $listingsQuery = MariachiListing::query()
            ->with([
                'mariachiProfile:id,user_id,business_name,city_name',
                'mariachiProfile.user:id,name,first_name,last_name,email',
                'marketplaceCity:id,name',
                'reviewedBy:id,name,first_name,last_name',
                'photos:id,mariachi_listing_id,path,sort_order',
            ])
            ->withCount(['photos', 'videos', 'reviews', 'quoteConversations']);

        if ($reviewStatus !== 'all' && in_array($reviewStatus, MariachiListing::REVIEW_STATUSES, true)) {
            $listingsQuery->where('review_status', $reviewStatus);
        }

        if ($paymentStatus !== 'all' && in_array($paymentStatus, MariachiListing::PAYMENT_STATUSES, true)) {
            $listingsQuery->where('payment_status', $paymentStatus);
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
                case payment_status
                    when 'pending' then 0
                    when 'rejected' then 1
                    when 'approved' then 2
                    else 3
                end,
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

        $listingMetrics = [
            'pending' => (int) ($statusTotals[MariachiListing::REVIEW_PENDING] ?? 0),
            'approved' => (int) ($statusTotals[MariachiListing::REVIEW_APPROVED] ?? 0),
            'rejected' => (int) ($statusTotals[MariachiListing::REVIEW_REJECTED] ?? 0),
            'live' => MariachiListing::query()->published()->count(),
            'payment_pending' => MariachiListing::query()->where('payment_status', MariachiListing::PAYMENT_PENDING)->count(),
            'total' => MariachiListing::query()->count(),
        ];

        $cities = MariachiListing::query()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->orderBy('city_name')
            ->distinct()
            ->pluck('city_name');

        return view('content.admin.listings-index', [
            'listings' => $listings,
            'reviewStatus' => $reviewStatus,
            'paymentStatus' => $paymentStatus,
            'city' => $city,
            'search' => $search,
            'reason' => $reason,
            'cities' => $cities,
            'statuses' => MariachiListing::REVIEW_STATUSES,
            'paymentStatuses' => MariachiListing::PAYMENT_STATUSES,
            'statusTotals' => $statusTotals,
            'listingMetrics' => $listingMetrics,
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
            'latestPayment.reviewedBy:id,name,first_name,last_name',
        ])->loadCount(['quoteConversations', 'reviews']);

        $latestPayment = $listing->latestPayment;

        $activityTimeline = collect([
            [
                'title' => 'Anuncio creado',
                'body' => 'El mariachi inicio la ficha base del anuncio en el panel.',
                'meta' => 'Creacion inicial',
                'at' => $listing->created_at,
                'point' => 'primary',
            ],
            [
                'title' => 'Comprobante enviado',
                'body' => 'El mariachi envio un comprobante manual por Nequi para este anuncio.',
                'meta' => $latestPayment
                    ? '$'.number_format((int) $latestPayment->amount_cop, 0, ',', '.').' COP · '.strtoupper($latestPayment->method)
                    : 'Pago manual',
                'at' => $latestPayment?->created_at,
                'point' => 'warning',
            ],
            [
                'title' => $latestPayment?->status === ListingPayment::STATUS_REJECTED
                    ? 'Pago rechazado'
                    : 'Pago validado',
                'body' => $latestPayment?->status === ListingPayment::STATUS_REJECTED
                    ? 'El comprobante fue rechazado y el anuncio sigue sin publicarse.'
                    : 'El pago fue validado y ya puede activar beneficios y publicación.',
                'meta' => $latestPayment?->reviewedBy?->display_name ?: 'Revision de pago',
                'at' => $latestPayment?->reviewed_at,
                'point' => $latestPayment?->status === ListingPayment::STATUS_REJECTED ? 'danger' : 'success',
            ],
            [
                'title' => 'Enviado a revision',
                'body' => 'El anuncio fue enviado al equipo admin para revisar contenido, media y catalogos.',
                'meta' => 'Estado editorial: '.($listing->review_status ?: MariachiListing::REVIEW_DRAFT),
                'at' => $listing->submitted_for_review_at,
                'point' => 'warning',
            ],
            [
                'title' => $listing->review_status === MariachiListing::REVIEW_REJECTED
                    ? 'Revision rechazada'
                    : 'Revision completada',
                'body' => $listing->review_status === MariachiListing::REVIEW_REJECTED
                    ? 'El anuncio fue devuelto al mariachi con ajustes pendientes antes de publicarse.'
                    : 'El equipo admin reviso el anuncio y lo dejo listo para su siguiente etapa.',
                'meta' => $listing->reviewedBy?->display_name ?: 'Revision admin',
                'at' => $listing->reviewed_at,
                'point' => $listing->review_status === MariachiListing::REVIEW_REJECTED ? 'danger' : 'success',
            ],
            [
                'title' => 'Activado en el marketplace',
                'body' => 'El anuncio ya puede aparecer en resultados y landings publicas.',
                'meta' => 'Estado operativo: '.($listing->status ?: MariachiListing::STATUS_DRAFT),
                'at' => $listing->activated_at,
                'point' => 'info',
            ],
            [
                'title' => 'Ultima actualizacion',
                'body' => 'Ultimo cambio detectado en contenido, media o configuracion general del anuncio.',
                'meta' => 'Completitud '.(int) $listing->listing_completion.'%',
                'at' => $listing->updated_at,
                'point' => 'secondary',
            ],
        ])->filter(fn (array $item): bool => filled($item['at']))->values();

        return view('content.admin.listings-show', [
            'listing' => $listing,
            'activityTimeline' => $activityTimeline,
        ]);
    }

    public function moderate(Request $request, MariachiListing $listing): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $pendingPayment = $listing->payments()
            ->where('status', ListingPayment::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($pendingPayment) {
            if ($validated['action'] === 'approve') {
                return $this->approvePendingPayment($request, $listing, $pendingPayment);
            }

            return $this->rejectPendingPayment($request, $listing, $pendingPayment, (string) $validated['rejection_reason']);
        }

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

    private function approvePendingPayment(Request $request, MariachiListing $listing, ListingPayment $payment): RedirectResponse
    {
        $profile = $listing->mariachiProfile;
        abort_unless($profile, 404);

        $plan = Plan::query()->where('code', $payment->plan_code)->first();

        if (! $plan) {
            return redirect()
                ->route('admin.listings.show', $listing)
                ->withErrors([
                    'payment' => 'No encontramos el plan asociado a este comprobante. Revisa la configuracion del catalogo de planes.',
                ]);
        }

        $reviewerId = $request->user()->id;

        DB::transaction(function () use ($listing, $payment, $profile, $plan, $reviewerId): void {
            $payment->update([
                'status' => ListingPayment::STATUS_APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->planAssignmentService->assignToProfile(
                $profile,
                $plan,
                $listing,
                'nequi_manual_payment',
                [
                    'listing_payment_id' => $payment->id,
                    'reviewed_by_user_id' => $reviewerId,
                    'method' => $payment->method,
                ],
                true
            );

            $listing->update([
                'selected_plan_code' => $payment->plan_code,
                'plan_selected_at' => $listing->plan_selected_at ?? $payment->created_at ?? now(),
                'payment_status' => MariachiListing::PAYMENT_APPROVED,
                'review_status' => MariachiListing::REVIEW_APPROVED,
                'submitted_for_review_at' => $listing->submitted_for_review_at ?? $payment->created_at ?? now(),
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewerId,
                'rejection_reason' => null,
                'status' => MariachiListing::STATUS_ACTIVE,
                'is_active' => true,
                'activated_at' => $listing->activated_at ?? now(),
                'deactivated_at' => null,
            ]);
        });

        return redirect()
            ->route('admin.listings.show', $listing)
            ->with('status', 'Pago aprobado. La suscripcion quedo activa y el anuncio ya puede publicarse.');
    }

    private function rejectPendingPayment(
        Request $request,
        MariachiListing $listing,
        ListingPayment $payment,
        string $reason
    ): RedirectResponse {
        $reviewerId = $request->user()->id;

        DB::transaction(function () use ($listing, $payment, $reviewerId, $reason): void {
            $payment->update([
                'status' => ListingPayment::STATUS_REJECTED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $listing->update([
                'payment_status' => MariachiListing::PAYMENT_REJECTED,
                'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
                'is_active' => false,
                'review_status' => MariachiListing::REVIEW_DRAFT,
                'submitted_for_review_at' => null,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'rejection_reason' => null,
                'deactivated_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.listings.show', $listing)
            ->with('status', 'Pago rechazado. El mariachi puede volver a intentar con un nuevo comprobante.');
    }
}
