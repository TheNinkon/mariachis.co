<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfileHandleAlias;
use App\Models\MariachiProfile;
use App\Models\VerificationRequest;
use App\Services\ProfileVerificationCatalogService;
use App\Services\WompiPaymentFlowService;
use App\Services\WompiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiVerificationController extends Controller
{
    public function __construct(
        private readonly ProfileVerificationCatalogService $verificationCatalog,
        private readonly WompiPaymentFlowService $paymentFlows,
        private readonly WompiService $wompi
    ) {
    }

    public function edit(): View
    {
        $profile = $this->providerProfile()->loadMissing([
            'verificationRequests.payment.reviewedBy:id,name,first_name,last_name',
            'verificationPayments.reviewedBy:id,name,first_name,last_name',
        ]);
        $latestRequest = $profile->verificationRequests->first();
        $latestPayment = $profile->verificationPayments->first();
        $verificationPlans = $this->verificationCatalog->plans();
        $canSubmitVerification = ! $profile->hasActiveVerification();

        return view('content.mariachi.verification', [
            'profile' => $profile,
            'latestRequest' => $latestRequest,
            'latestPayment' => $latestPayment,
            'verificationPlans' => $verificationPlans,
            'wompi' => $this->wompi->publicConfig(),
            'canSubmitVerification' => $canSubmitVerification,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();
        $plans = $this->verificationCatalog->plans();
        $latestRequest = $profile->verificationRequests()->latest('submitted_at')->latest('id')->first();
        $latestPayment = $profile->verificationPayments()->latest('created_at')->latest('id')->first();

        if ($profile->hasActiveVerification()) {
            $expiresAt = $profile->verification_expires_at?->format('Y-m-d');

            return back()->withErrors([
                'verification' => $expiresAt
                    ? 'Ya tienes una verificacion activa hasta '.$expiresAt.'. Podras renovarla cuando venza.'
                    : 'Ya tienes una verificacion activa. No necesitas comprar otra por ahora.',
            ]);
        }

        if (! $this->paymentFlows->isConfigured()) {
            return back()->withErrors([
                'verification' => 'Wompi no está configurado en este momento. Intenta más tarde o contacta a soporte.',
            ]);
        }

        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($plans))],
            'id_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = $this->verificationCatalog->plan($validated['plan_code']);
        if (! $plan) {
            return back()->withErrors([
                'plan_code' => 'El plan de verificación seleccionado no es válido.',
            ]);
        }

        if (
            $latestRequest?->status === VerificationRequest::STATUS_PENDING
            && $latestPayment
            && ! $latestPayment->isPending()
        ) {
            return back()->withErrors([
                'verification' => 'Tu pago ya fue confirmado y ahora solo falta la revisión manual de documentos.',
            ]);
        }

        if (
            $latestRequest?->status === VerificationRequest::STATUS_PENDING
            && $latestPayment?->isPending()
            && $latestPayment->plan_code !== $plan['code']
        ) {
            return back()->withErrors([
                'plan_code' => 'Ya tienes un checkout Wompi pendiente con otro plan. Retómalo o espera a que termine antes de cambiarlo.',
            ]);
        }

        $idDocumentPath = $request->file('id_document')->store('verification-docs/id', 'public');
        $identityProofPath = $request->file('identity_proof')->store('verification-docs/proof', 'public');

        return redirect()->away(
            $this->paymentFlows->beginVerificationCheckout($profile, $plan, [
                'notes' => $validated['notes'] ?? null,
                'id_document_path' => $idDocumentPath,
                'identity_proof_path' => $identityProofPath,
            ])
        );
    }

    public function updateHandle(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();

        if (! $profile->hasActiveVerification()) {
            return back()->withErrors([
                'handle' => 'El handle personalizado solo se puede editar con una verificación activa.',
            ]);
        }

        $validated = $request->validate([
            'handle' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $handle = strtolower(trim($validated['handle']));

        if ($this->verificationCatalog->isReservedHandle($handle)) {
            return back()->withErrors([
                'handle' => 'Ese handle está reservado. Elige otro.',
            ]);
        }

        $exists = MariachiProfile::query()
            ->where('slug', $handle)
            ->where('id', '!=', $profile->id)
            ->exists();

        $aliasInUse = MariachiProfileHandleAlias::query()
            ->where('old_slug', $handle)
            ->where('mariachi_profile_id', '!=', $profile->id)
            ->exists();

        if ($exists || $aliasInUse) {
            return back()->withErrors([
                'handle' => 'Ese handle ya está en uso.',
            ]);
        }

        DB::transaction(function () use ($profile, $handle): void {
            $currentSlug = (string) $profile->slug;

            if ($currentSlug !== '' && $currentSlug !== $handle) {
                MariachiProfileHandleAlias::query()
                    ->where('mariachi_profile_id', $profile->id)
                    ->where('old_slug', $handle)
                    ->delete();

                MariachiProfileHandleAlias::query()->updateOrCreate(
                    ['old_slug' => $currentSlug],
                    ['mariachi_profile_id' => $profile->id]
                );
            }

            $profile->update([
                'slug' => $handle,
                'slug_locked' => true,
            ]);
        });

        return back()->with('status', 'Handle personalizado actualizado.');
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
}
