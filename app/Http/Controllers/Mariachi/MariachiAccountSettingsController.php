<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use App\Services\EntitlementsService;
use App\Services\ProfileVerificationCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MariachiAccountSettingsController extends Controller
{
    public function __construct(
        private readonly EntitlementsService $entitlementsService,
        private readonly ProfileVerificationCatalogService $verificationCatalog
    ) {
    }

    public function security(): View
    {
        return view('content.mariachi.account-security', [
            'user' => auth()->user(),
            'profile' => $this->providerProfile(),
        ]);
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Tu contraseña fue actualizada.');
    }

    public function notifications(): View
    {
        $profile = $this->providerProfile();

        return view('content.mariachi.account-notifications', [
            'profile' => $profile,
            'preferences' => $this->notificationPreferences($profile),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();

        $validated = $request->validate([
            'quotes_email' => ['nullable', 'boolean'],
            'reviews_email' => ['nullable', 'boolean'],
            'payments_email' => ['nullable', 'boolean'],
            'product_email' => ['nullable', 'boolean'],
        ]);

        $profile->update([
            'notification_preferences' => [
                'quotes_email' => (bool) ($validated['quotes_email'] ?? false),
                'reviews_email' => (bool) ($validated['reviews_email'] ?? false),
                'payments_email' => (bool) ($validated['payments_email'] ?? false),
                'product_email' => (bool) ($validated['product_email'] ?? false),
            ],
        ]);

        return back()->with('status', 'Preferencias de notificación actualizadas.');
    }

    public function billing(): View
    {
        $profile = $this->providerProfile()->loadMissing([
            'activeSubscription.plan',
            'verificationPayments.reviewedBy:id,name,first_name,last_name',
        ]);

        return view('content.mariachi.account-billing', [
            'profile' => $profile,
            'planSummary' => $this->entitlementsService->summary($profile),
            'verificationPlans' => $this->verificationCatalog->plans(),
            'verificationPayments' => $profile->verificationPayments()->latest('id')->limit(10)->get(),
        ]);
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = $user->mariachiProfile()->firstOrCreate([], [
            'business_name' => $user->display_name,
            'city_name' => null,
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
        ]);

        $shouldRefresh = false;

        if (! filled($profile->business_name)) {
            $profile->ensureBusinessNameFromUser();
            $shouldRefresh = true;
        }

        if (! filled($profile->slug) && ! $profile->slug_locked) {
            $profile->ensureSlug();
            $shouldRefresh = true;
        }

        if ($shouldRefresh) {
            $profile->refresh();
        }

        return $profile;
    }

    /**
     * @return array{
     *   quotes_email:bool,
     *   reviews_email:bool,
     *   payments_email:bool,
     *   product_email:bool
     * }
     */
    private function notificationPreferences(MariachiProfile $profile): array
    {
        $stored = is_array($profile->notification_preferences) ? $profile->notification_preferences : [];

        return [
            'quotes_email' => (bool) ($stored['quotes_email'] ?? true),
            'reviews_email' => (bool) ($stored['reviews_email'] ?? true),
            'payments_email' => (bool) ($stored['payments_email'] ?? true),
            'product_email' => (bool) ($stored['product_email'] ?? false),
        ];
    }
}
