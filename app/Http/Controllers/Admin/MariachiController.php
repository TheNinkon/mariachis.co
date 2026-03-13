<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use App\Models\Plan;
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

        $recentActivity = $this->buildRecentActivity($profile);

        return view('content.admin.mariachis-show', [
            'mariachi' => $user,
            'profile' => $profile,
            'recentActivity' => $recentActivity,
            'planSummary' => $this->entitlementsService->summary($profile),
            'planIssues' => $this->entitlementsService->profileAdjustmentIssues($profile),
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
}
