<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountActivationPayment;
use App\Models\MariachiProfile;
use App\Models\Plan;
use App\Models\ProfileVerificationPayment;
use App\Models\User;
use App\Services\EntitlementsService;
use App\Services\PlanAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiController extends Controller
{
    public function __construct(
        private readonly EntitlementsService $entitlementsService,
        private readonly PlanAssignmentService $planAssignmentService
    ) {
    }

    public function index(): View
    {
        $mariachis = User::query()
            ->where('role', User::ROLE_MARIACHI)
            ->with([
                'mariachiProfile' => function ($query): void {
                    $query
                        ->withCount(['listings', 'activeListings', 'reviews', 'quoteConversations'])
                        ->with(['activeSubscription.plan']);
                },
            ])
            ->latest()
            ->get();

        $totalMariachis = $mariachis->count();
        $activeMariachis = $mariachis->where('status', User::STATUS_ACTIVE)->count();
        $subscribedMariachis = $mariachis->filter(fn (User $mariachi): bool => (bool) $mariachi->mariachiProfile?->activeSubscription)->count();
        $pendingVerificationMariachis = User::query()
            ->where('role', User::ROLE_MARIACHI)
            ->whereHas('mariachiProfile.verificationRequests', function ($query): void {
                $query->where('status', 'pending');
            })
            ->count();

        return view('content.admin.mariachis-index', [
            'mariachis' => $mariachis,
            'totalMariachis' => $totalMariachis,
            'activeMariachis' => $activeMariachis,
            'subscribedMariachis' => $subscribedMariachis,
            'pendingVerificationMariachis' => $pendingVerificationMariachis,
        ]);
    }

    public function show(User $user): View
    {
        $this->ensureMariachiUser($user);

        $profile = $this->providerProfile($user)
            ->loadCount(['listings', 'activeListings', 'reviews', 'quoteConversations'])
            ->load([
                'stat',
                'activeSubscription.plan.entitlements',
                'verificationRequests.reviewedBy' => fn ($query) => $query->latest('submitted_at')->limit(8),
                'listings' => fn ($query) => $query
                    ->withCount(['photos', 'videos', 'quoteConversations', 'reviews'])
                    ->with(['photos', 'videos', 'serviceAreas'])
                    ->latest('updated_at')
                    ->limit(10),
                'reviews' => fn ($query) => $query
                    ->with(['clientUser', 'mariachiListing'])
                    ->latest('created_at')
                    ->limit(10),
                'quoteConversations' => fn ($query) => $query
                    ->with(['clientUser', 'mariachiListing'])
                    ->latest('last_message_at')
                    ->limit(10),
            ]);
        $activationPayments = $user->activationPayments()
            ->with([
                'plan:id,code,name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->latest('id')
            ->get();
        $verificationPayments = $profile->verificationPayments()
            ->with([
                'verificationRequest.reviewedBy:id,name,first_name,last_name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->latest('id')
            ->get();
        $profilePayments = $this->buildProfilePayments($user, $profile, $activationPayments, $verificationPayments);

        $recentActivity = $this->buildRecentActivity($profile);

        return view('content.admin.mariachis-show', [
            'mariachi' => $user,
            'profile' => $profile,
            'recentActivity' => $recentActivity,
            'planSummary' => $this->entitlementsService->summary($profile),
            'planIssues' => $this->entitlementsService->profileAdjustmentIssues($profile),
            'profilePayments' => $profilePayments,
            'profilePaymentSummary' => [
                'total' => $profilePayments->count(),
                'activation_count' => $profilePayments->where('source_type', 'activation')->count(),
                'verification_count' => $profilePayments->where('source_type', 'verification')->count(),
                'pending_count' => $profilePayments->where('is_pending', true)->count(),
            ],
        ]);
    }

    public function edit(User $user): View
    {
        $this->ensureMariachiUser($user);

        $profile = $this->providerProfile($user)->loadMissing('activeSubscription.plan.entitlements');

        return view('content.admin.mariachis-edit', [
            'mariachi' => $user,
            'profile' => $profile,
            'plans' => Plan::query()->active()->orderBy('sort_order')->orderBy('id')->get(),
            'planSummary' => $this->entitlementsService->summary($profile),
            'planIssues' => $this->entitlementsService->profileAdjustmentIssues($profile),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureMariachiUser($user);

        $profile = $this->providerProfile($user);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:140'],
            'responsible_name' => ['required', 'string', 'max:140'],
            'short_description' => ['required', 'string', 'max:280'],
            'city_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $logoPath = $profile->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('mariachi-provider-logos', 'public');
        }

        $profile->update([
            'business_name' => $validated['business_name'],
            'responsible_name' => $validated['responsible_name'],
            'short_description' => $validated['short_description'],
            'city_name' => $validated['city_name'],
            'whatsapp' => $validated['whatsapp'] ?: null,
            'website' => $validated['website'] ?: null,
            'instagram' => $validated['instagram'] ?: null,
            'facebook' => $validated['facebook'] ?: null,
            'tiktok' => $validated['tiktok'] ?: null,
            'youtube' => $validated['youtube'] ?: null,
            'logo_path' => $logoPath,
            'profile_completed' => true,
            'stage_status' => 'provider_ready',
        ]);

        $profile->ensureSlug();

        $user->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ]);

        if (! $profile->default_mariachi_listing_id && $profile->listings()->exists()) {
            $profile->update([
                'default_mariachi_listing_id' => $profile->listings()->latest('updated_at')->value('id'),
            ]);
        }

        return redirect()
            ->route('admin.mariachis.show', $user)
            ->with('status', 'Perfil del mariachi actualizado.');
    }

    public function assignPlan(Request $request, User $user): RedirectResponse
    {
        $this->ensureMariachiUser($user);

        $profile = $this->providerProfile($user);

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
        ]);

        $plan = Plan::query()->active()->findOrFail((int) $validated['plan_id']);

        $this->planAssignmentService->assignToProfile(
            $profile,
            $plan,
            null,
            'admin_assignment',
            ['assigned_by_user_id' => $request->user()->id]
        );

        return redirect()
            ->route('admin.mariachis.edit', $user)
            ->with('status', 'Plan asignado al mariachi.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureMariachiUser($user);

        $nextStatus = $user->status === User::STATUS_ACTIVE
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        $user->update(['status' => $nextStatus]);

        return back()->with('status', 'Estado de mariachi actualizado.');
    }

    private function ensureMariachiUser(User $user): void
    {
        if ($user->role !== User::ROLE_MARIACHI) {
            abort(404);
        }
    }

    private function providerProfile(User $user): MariachiProfile
    {
        return $user->mariachiProfile()->firstOrCreate([], [
            'city_name' => null,
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);
    }

    private function buildRecentActivity(MariachiProfile $profile): Collection
    {
        $listingEvents = $profile->listings->map(function ($listing): array {
            return [
                'at' => $listing->updated_at,
                'point' => 'primary',
                'title' => 'Anuncio actualizado',
                'body' => $listing->title ?: 'Anuncio sin titulo',
                'meta' => sprintf(
                    '%s · %s%% de completitud',
                    $listing->city_name ?: 'Ubicacion pendiente',
                    (int) $listing->listing_completion
                ),
            ];
        });

        $reviewEvents = $profile->reviews->map(function ($review): array {
            $clientName = $review->clientUser?->display_name ?: 'Cliente';

            return [
                'at' => $review->created_at,
                'point' => 'success',
                'title' => sprintf('Nueva opinion de %s', $clientName),
                'body' => $review->title ?: 'Opinion sin titulo',
                'meta' => sprintf('%d estrella(s) · %s', (int) $review->rating, $review->moderation_status),
            ];
        });

        $quoteEvents = $profile->quoteConversations->map(function ($conversation): array {
            $clientName = $conversation->clientUser?->display_name ?: 'Cliente';
            $listingTitle = $conversation->mariachiListing?->title ?: 'Sin anuncio vinculado';

            return [
                'at' => $conversation->last_message_at ?: $conversation->created_at,
                'point' => 'info',
                'title' => sprintf('Solicitud de %s', $clientName),
                'body' => $listingTitle,
                'meta' => sprintf('%s · %s', $conversation->event_city ?: 'Ciudad pendiente', $conversation->status),
            ];
        });

        $verificationEvents = $profile->verificationRequests->map(function ($request): array {
            return [
                'at' => $request->submitted_at ?: $request->created_at,
                'point' => 'warning',
                'title' => 'Actualizacion de verificacion',
                'body' => $request->status === 'approved'
                    ? 'Solicitud aprobada'
                    : ($request->status === 'rejected' ? 'Solicitud rechazada' : 'Solicitud enviada'),
                'meta' => $request->reviewedBy?->display_name
                    ? 'Revisado por '.$request->reviewedBy->display_name
                    : 'Pendiente de revision',
            ];
        });

        return collect()
            ->merge($listingEvents)
            ->merge($reviewEvents)
            ->merge($quoteEvents)
            ->merge($verificationEvents)
            ->filter(fn (array $event): bool => filled($event['at']))
            ->sortByDesc('at')
            ->values()
            ->take(8);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\AccountActivationPayment>  $activationPayments
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProfileVerificationPayment>  $verificationPayments
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function buildProfilePayments(
        User $user,
        MariachiProfile $profile,
        Collection $activationPayments,
        Collection $verificationPayments
    ): Collection {
        $activationRows = $activationPayments->map(function (AccountActivationPayment $payment) use ($profile, $user): object {
            return (object) [
                'source_type' => 'activation',
                'source_label' => 'Activacion',
                'source_badge_class' => 'primary',
                'id' => $payment->id,
                'created_at' => $payment->created_at,
                'sort_key' => sprintf('%010d-%010d-activation', $payment->created_at?->timestamp ?? 0, $payment->id),
                'operation_label' => 'Activacion de cuenta',
                'operation_detail' => $payment->plan?->name ?: 'Plan de activacion',
                'subject_meta' => trim(($user->email ?: 'Sin email').' · '.($profile->city_name ?: 'Sin ciudad')),
                'amount_cop' => (int) $payment->amount_cop,
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
                'review_meta' => 'Cuenta '.$user->statusLabel(),
                'rejection_reason' => $payment->rejection_reason,
                'period_label' => null,
                'is_pending' => $payment->isPending(),
                'approve_url' => $payment->isPending() ? route('admin.account-activation-payments.update', $payment) : null,
                'reject_url' => $payment->isPending() ? route('admin.account-activation-payments.update', $payment) : null,
                'empty_state' => 'Sin acciones pendientes',
            ];
        });

        $verificationRows = $verificationPayments->map(function (ProfileVerificationPayment $payment) use ($profile): object {
            $request = $payment->verificationRequest;
            $requestStatusLabel = match ($request?->status) {
                'approved' => 'Solicitud aprobada',
                'rejected' => 'Solicitud rechazada',
                'pending' => 'Solicitud pendiente',
                default => 'Sin solicitud vinculada',
            };
            $isPending = $request
                ? $request->status === 'pending'
                : $payment->isPending();
            $approveUrl = $request && $request->status === 'pending'
                ? route('admin.profile-verifications.update', $request)
                : null;
            $rejectUrl = $approveUrl;
            $periodLabel = null;

            if ($payment->starts_at || $payment->ends_at) {
                $periodLabel = trim(sprintf(
                    'Vigencia %s %s',
                    $payment->starts_at?->format('d/m/Y') ?: '-',
                    $payment->ends_at ? 'a '.$payment->ends_at->format('d/m/Y') : ''
                ));
            }

            return (object) [
                'source_type' => 'verification',
                'source_label' => 'Verificacion',
                'source_badge_class' => 'info',
                'id' => $payment->id,
                'created_at' => $payment->created_at,
                'sort_key' => sprintf('%010d-%010d-verification', $payment->created_at?->timestamp ?? 0, $payment->id),
                'operation_label' => 'Verificacion de perfil',
                'operation_detail' => sprintf(
                    '%s · %d mes(es)',
                    \Illuminate\Support\Str::headline((string) $payment->plan_code),
                    max(1, (int) $payment->duration_months)
                ),
                'subject_meta' => trim(($profile->city_name ?: 'Sin ciudad').' · '.$requestStatusLabel),
                'amount_cop' => (int) $payment->amount_cop,
                'checkout_reference' => $payment->checkout_reference ?: $payment->reference_text ?: '-',
                'provider_transaction_id' => $payment->provider_transaction_id ?: 'Sin transacción',
                'provider_transaction_status' => $payment->provider_transaction_status,
                'status_label' => $payment->statusLabel(),
                'status_class' => match ($payment->status) {
                    ProfileVerificationPayment::STATUS_APPROVED => 'success',
                    ProfileVerificationPayment::STATUS_REJECTED => 'danger',
                    default => 'warning',
                },
                'reviewed_at' => $request?->reviewed_at ?: $payment->reviewed_at,
                'reviewed_by_name' => $request?->reviewedBy?->display_name ?: $payment->reviewedBy?->display_name ?: 'Sin revisor',
                'review_meta' => $requestStatusLabel,
                'rejection_reason' => $request?->rejection_reason ?: $payment->rejection_reason,
                'period_label' => $periodLabel,
                'is_pending' => $isPending,
                'approve_url' => $approveUrl,
                'reject_url' => $rejectUrl,
                'empty_state' => $request ? 'Revisado desde verificacion' : 'Sin solicitud vinculada',
            ];
        });

        return $activationRows
            ->concat($verificationRows)
            ->sortByDesc('sort_key')
            ->values();
    }
}
