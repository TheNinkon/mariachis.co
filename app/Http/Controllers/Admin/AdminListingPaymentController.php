<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountActivationPayment;
use App\Models\ListingPayment;
use App\Services\ListingPaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminListingPaymentController extends Controller
{
    public function __construct(
        private readonly ListingPaymentService $listingPaymentService
    ) {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $operation = (string) $request->query('operation', 'all');
        $city = trim((string) $request->query('city', ''));
        $search = trim((string) $request->query('search', ''));

        $listingQuery = ListingPayment::query()
            ->with([
                'listing:id,mariachi_profile_id,title,slug,city_name,status,review_status,payment_status,is_active',
                'listing.mariachiProfile:id,user_id,business_name,city_name',
                'listing.mariachiProfile.user:id,name,first_name,last_name,email',
                'reviewedBy:id,name,first_name,last_name',
                'retryOf:id,checkout_reference,target_plan_code',
            ])
            ->latest('created_at')
            ->latest('id');

        if ($status !== 'all' && in_array($status, [
            ListingPayment::STATUS_PENDING,
            ListingPayment::STATUS_APPROVED,
            ListingPayment::STATUS_REJECTED,
        ], true)) {
            $listingQuery->where('status', $status);
        }

        if ($operation !== 'all' && in_array($operation, [
            ListingPayment::OPERATION_INITIAL,
            ListingPayment::OPERATION_UPGRADE,
            ListingPayment::OPERATION_RENEWAL,
            ListingPayment::OPERATION_RETRY,
        ], true)) {
            $listingQuery->where('operation_type', $operation);
        }

        if ($city !== '') {
            $listingQuery->whereHas('listing', function ($relatedQuery) use ($city): void {
                $relatedQuery->whereRaw('LOWER(city_name) = ?', [mb_strtolower($city)]);
            });
        }

        if ($search !== '') {
            $term = '%'.$search.'%';

            $listingQuery->where(function ($paymentQuery) use ($term): void {
                $paymentQuery
                    ->where('checkout_reference', 'like', $term)
                    ->orWhere('provider_transaction_id', 'like', $term)
                    ->orWhere('target_plan_code', 'like', $term)
                    ->orWhereHas('listing', function ($listingQuery) use ($term): void {
                        $listingQuery
                            ->where('title', 'like', $term)
                            ->orWhere('slug', 'like', $term)
                            ->orWhere('city_name', 'like', $term)
                            ->orWhereHas('mariachiProfile', function ($profileQuery) use ($term): void {
                                $profileQuery
                                    ->where('business_name', 'like', $term)
                                    ->orWhereHas('user', function ($userQuery) use ($term): void {
                                        $userQuery
                                            ->where('name', 'like', $term)
                                            ->orWhere('email', 'like', $term);
                                    });
                            });
                    });
            });
        }

        $listingPayments = $listingQuery->get();

        $activationPayments = $this->activationPaymentsForIndex($status, $operation, $city, $search);
        $payments = $this->paginateMergedPayments(
            $request,
            $this->normalizePaymentsForIndex($listingPayments, $activationPayments)
        );

        $listingTotals = ListingPayment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activationTotals = AccountActivationPayment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totals = collect([
            ListingPayment::STATUS_PENDING => (int) ($listingTotals[ListingPayment::STATUS_PENDING] ?? 0)
                + (int) ($activationTotals[AccountActivationPayment::STATUS_PENDING_REVIEW] ?? 0),
            ListingPayment::STATUS_APPROVED => (int) ($listingTotals[ListingPayment::STATUS_APPROVED] ?? 0)
                + (int) ($activationTotals[AccountActivationPayment::STATUS_APPROVED] ?? 0),
            ListingPayment::STATUS_REJECTED => (int) ($listingTotals[ListingPayment::STATUS_REJECTED] ?? 0)
                + (int) ($activationTotals[AccountActivationPayment::STATUS_REJECTED] ?? 0),
        ]);

        $operationTotals = ListingPayment::query()
            ->selectRaw('operation_type, count(*) as total')
            ->groupBy('operation_type')
            ->pluck('total', 'operation_type');
        $operationTotals = collect($operationTotals)->put(
            'activation',
            (int) AccountActivationPayment::query()->count()
        );

        $approvedRevenue = (int) ListingPayment::query()
            ->where('status', ListingPayment::STATUS_APPROVED)
            ->sum(DB::raw('COALESCE(final_amount_cop, amount_cop)'))
            + (int) AccountActivationPayment::query()
                ->where('status', AccountActivationPayment::STATUS_APPROVED)
                ->sum('amount_cop');

        $approvedCredits = (int) ListingPayment::query()
            ->where('status', ListingPayment::STATUS_APPROVED)
            ->sum('applied_credit_cop');

        $cities = \App\Models\MariachiListing::query()
            ->whereNotNull('city_name')
            ->where('city_name', '!=', '')
            ->orderBy('city_name')
            ->distinct()
            ->pluck('city_name')
            ->merge(
                \App\Models\MariachiProfile::query()
                    ->whereNotNull('city_name')
                    ->where('city_name', '!=', '')
                    ->orderBy('city_name')
                    ->distinct()
                    ->pluck('city_name')
            )
            ->unique()
            ->values();

        return view('content.admin.listing-payments-index', [
            'payments' => $payments,
            'status' => $status,
            'operation' => $operation,
            'city' => $city,
            'search' => $search,
            'totals' => $totals,
            'operationTotals' => $operationTotals,
            'approvedRevenue' => $approvedRevenue,
            'approvedCredits' => $approvedCredits,
            'cities' => $cities,
            'statuses' => [
                ListingPayment::STATUS_PENDING,
                ListingPayment::STATUS_APPROVED,
                ListingPayment::STATUS_REJECTED,
            ],
            'operations' => [
                'activation',
                ListingPayment::OPERATION_INITIAL,
                ListingPayment::OPERATION_UPGRADE,
                ListingPayment::OPERATION_RENEWAL,
                ListingPayment::OPERATION_RETRY,
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\AccountActivationPayment>
     */
    private function activationPaymentsForIndex(string $status, string $operation, string $city, string $search): Collection
    {
        if ($operation !== 'all' && $operation !== 'activation') {
            return collect();
        }

        $query = AccountActivationPayment::query()
            ->with([
                'user:id,name,first_name,last_name,email,phone,status,activation_paid_at',
                'user.mariachiProfile:id,user_id,business_name,city_name',
                'plan:id,code,name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->latest('id');

        if ($status !== 'all') {
            $activationStatus = match ($status) {
                ListingPayment::STATUS_PENDING => AccountActivationPayment::STATUS_PENDING_REVIEW,
                ListingPayment::STATUS_APPROVED => AccountActivationPayment::STATUS_APPROVED,
                ListingPayment::STATUS_REJECTED => AccountActivationPayment::STATUS_REJECTED,
                default => null,
            };

            if ($activationStatus === null) {
                return collect();
            }

            $query->where('status', $activationStatus);
        }

        if ($city !== '') {
            $query->whereHas('user.mariachiProfile', function ($profileQuery) use ($city): void {
                $profileQuery->whereRaw('LOWER(city_name) = ?', [mb_strtolower($city)]);
            });
        }

        if ($search !== '') {
            $term = '%'.$search.'%';

            $query->where(function ($paymentQuery) use ($term): void {
                $paymentQuery
                    ->where('checkout_reference', 'like', $term)
                    ->orWhere('provider_transaction_id', 'like', $term)
                    ->orWhereHas('user', function ($userQuery) use ($term): void {
                        $userQuery
                            ->where('name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term)
                            ->orWhereHas('mariachiProfile', function ($profileQuery) use ($term): void {
                                $profileQuery->where('business_name', 'like', $term);
                            });
                    });
            });
        }

        return $query->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\ListingPayment>  $listingPayments
     * @param  \Illuminate\Support\Collection<int, \App\Models\AccountActivationPayment>  $activationPayments
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function normalizePaymentsForIndex(Collection $listingPayments, Collection $activationPayments): Collection
    {
        $listingRows = $listingPayments->map(function (ListingPayment $payment): object {
            $listing = $payment->listing;
            $providerName = $listing?->mariachiProfile?->business_name
                ?: $listing?->mariachiProfile?->user?->display_name
                ?: 'Mariachi';

            return (object) [
                'source_type' => 'listing',
                'source_label' => 'Anuncio',
                'id' => $payment->id,
                'created_at' => $payment->created_at,
                'sort_key' => sprintf('%010d-%010d-listing', $payment->created_at?->timestamp ?? 0, $payment->id),
                'record' => $payment,
                'subject_title' => $listing?->title ?: 'Anuncio #'.($listing?->id ?: $payment->mariachi_listing_id),
                'subject_url' => $listing ? route('admin.listings.show', $listing) : null,
                'subject_meta' => trim($providerName.' · '.($listing?->city_name ?: 'Sin ciudad')),
                'operation_label' => $payment->operationLabel(),
                'operation_detail' => trim(
                    ($payment->source_plan_code ? \Illuminate\Support\Str::headline($payment->source_plan_code).' → ' : '')
                    .\Illuminate\Support\Str::headline($payment->targetPlanCode())
                    .' · '.$payment->duration_months.' mes(es)'
                ),
                'amount_cop' => $payment->chargedAmountCop(),
                'base_amount_cop' => (int) ($payment->base_amount_cop ?: $payment->amount_cop),
                'applied_credit_cop' => (int) ($payment->applied_credit_cop ?: 0),
                'checkout_reference' => $payment->checkout_reference ?: $payment->reference_text ?: '-',
                'provider_transaction_id' => $payment->provider_transaction_id ?: 'Sin transacción',
                'provider_transaction_status' => $payment->provider_transaction_status,
                'status_label' => $payment->statusLabel(),
                'status_class' => match ($payment->status) {
                    ListingPayment::STATUS_APPROVED => 'success',
                    ListingPayment::STATUS_REJECTED => 'danger',
                    default => 'warning',
                },
                'reviewed_at' => $payment->reviewed_at,
                'reviewed_by_name' => $payment->reviewedBy?->display_name ?: 'Sin revisor',
                'rejection_reason' => $payment->rejection_reason,
                'is_pending' => $payment->isPending(),
                'approve_url' => route('admin.payments.update', $payment),
                'reject_url' => route('admin.payments.update', $payment),
            ];
        });

        $activationRows = $activationPayments->map(function (AccountActivationPayment $payment): object {
            $user = $payment->user;
            $profile = $user?->mariachiProfile;
            $displayName = $profile?->business_name ?: $user?->display_name ?: 'Cuenta pendiente';

            return (object) [
                'source_type' => 'activation',
                'source_label' => 'Activación',
                'id' => $payment->id,
                'created_at' => $payment->created_at,
                'sort_key' => sprintf('%010d-%010d-activation', $payment->created_at?->timestamp ?? 0, $payment->id),
                'record' => $payment,
                'subject_title' => $displayName,
                'subject_url' => null,
                'subject_meta' => trim(($user?->email ?: 'Sin email').' · '.($profile?->city_name ?: 'Sin ciudad')),
                'operation_label' => 'Activación de cuenta',
                'operation_detail' => $payment->plan?->name ?: 'Plan de activación',
                'amount_cop' => (int) $payment->amount_cop,
                'base_amount_cop' => (int) $payment->amount_cop,
                'applied_credit_cop' => 0,
                'checkout_reference' => $payment->checkout_reference ?: $payment->reference_text ?: '-',
                'provider_transaction_id' => $payment->provider_transaction_id ?: 'Sin transacción',
                'provider_transaction_status' => $payment->provider_transaction_status,
                'status_label' => $payment->statusLabel(),
                'status_class' => match ($payment->status) {
                    AccountActivationPayment::STATUS_APPROVED => 'success',
                    AccountActivationPayment::STATUS_REJECTED => 'danger',
                    default => 'warning',
                },
                'reviewed_at' => $payment->reviewed_at,
                'reviewed_by_name' => $payment->reviewedBy?->display_name ?: 'Sin revisor',
                'rejection_reason' => $payment->rejection_reason,
                'is_pending' => $payment->isPending(),
                'approve_url' => route('admin.account-activation-payments.update', $payment),
                'reject_url' => route('admin.account-activation-payments.update', $payment),
            ];
        });

        return $listingRows
            ->concat($activationRows)
            ->sortByDesc('sort_key')
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $payments
     */
    private function paginateMergedPayments(Request $request, Collection $payments): LengthAwarePaginator
    {
        $perPage = 18;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $payments->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $payments->count(),
            $perPage,
            $page,
            [
                'path' => route('admin.payments.index'),
                'query' => $request->query(),
            ]
        );
    }

    public function update(Request $request, ListingPayment $listingPayment): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        if (! $listingPayment->isPending()) {
            return back()->withErrors([
                'payment' => 'Solo puedes moderar pagos que sigan pendientes.',
            ]);
        }

        try {
            DB::transaction(function () use ($listingPayment, $validated, $request): void {
                if ($validated['action'] === 'approve') {
                    $this->listingPaymentService->approvePayment($listingPayment, $request->user()->id, [
                        'reviewed_at' => now(),
                    ]);

                    return;
                }

                $this->listingPaymentService->rejectPayment(
                    $listingPayment,
                    (string) $validated['rejection_reason'],
                    $request->user()->id,
                    [
                        'reviewed_at' => now(),
                    ]
                );
            });
        } catch (DomainException $exception) {
            return back()->withErrors([
                'payment' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', $validated['action'] === 'approve'
            ? 'Pago actualizado como aprobado.'
            : 'Pago actualizado como rechazado.');
    }
}
