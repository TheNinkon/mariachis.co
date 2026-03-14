<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfileHandleAlias;
use App\Models\MariachiProfile;
use App\Models\ProfileVerificationPayment;
use App\Models\VerificationRequest;
use App\Services\NequiPaymentSettingsService;
use App\Services\ProfileVerificationCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiVerificationController extends Controller
{
    public function __construct(
        private readonly NequiPaymentSettingsService $nequiSettings,
        private readonly ProfileVerificationCatalogService $verificationCatalog
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
        $canSubmitVerification = ! $profile->hasActiveVerification()
            && ! ($latestPayment?->isPending() || $latestRequest?->status === VerificationRequest::STATUS_PENDING);

        return view('content.mariachi.verification', [
            'profile' => $profile,
            'latestRequest' => $latestRequest,
            'latestPayment' => $latestPayment,
            'verificationPlans' => $verificationPlans,
            'nequi' => $this->nequiSettings->publicConfig(),
            'canSubmitVerification' => $canSubmitVerification,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();
        $plans = $this->verificationCatalog->plans();
        $nequi = $this->nequiSettings->publicConfig();
        $latestPayment = $profile->verificationPayments()->latest('id')->first();
        $latestRequest = $profile->verificationRequests()->latest('id')->first();

        if ($profile->hasActiveVerification()) {
            $expiresAt = $profile->verification_expires_at?->format('Y-m-d');

            return back()->withErrors([
                'verification' => $expiresAt
                    ? 'Ya tienes una verificacion activa hasta '.$expiresAt.'. Podras renovarla cuando venza.'
                    : 'Ya tienes una verificacion activa. No necesitas comprar otra por ahora.',
            ]);
        }

        if (! $nequi['is_configured']) {
            return back()->withErrors([
                'proof_image' => 'El pago por Nequi no está configurado en este momento. Intenta más tarde o contacta a soporte.',
            ]);
        }

        if (($latestPayment && $latestPayment->isPending()) || ($latestRequest && $latestRequest->status === VerificationRequest::STATUS_PENDING)) {
            return back()->withErrors([
                'verification' => 'Ya tienes una verificación en proceso. Espera la revisión del admin antes de enviar otra.',
            ]);
        }

        $validated = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys($plans))],
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'id_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reference_text' => ['nullable', 'string', 'max:120'],
        ]);

        $plan = $this->verificationCatalog->plan($validated['plan_code']);
        if (! $plan) {
            return back()->withErrors([
                'plan_code' => 'El plan de verificación seleccionado no es válido.',
            ]);
        }

        $proofPath = $request->file('proof_image')->store('verification-payments/proofs', 'public');
        $idDocumentPath = $request->file('id_document')->store('verification-docs/id', 'public');
        $identityProofPath = $request->file('identity_proof')->store('verification-docs/proof', 'public');

        DB::transaction(function () use ($profile, $validated, $plan, $proofPath, $idDocumentPath, $identityProofPath): void {
            $payment = ProfileVerificationPayment::query()->create([
                'mariachi_profile_id' => $profile->id,
                'plan_code' => $plan['code'],
                'duration_months' => $plan['duration_months'],
                'amount_cop' => $plan['amount_cop'],
                'method' => ProfileVerificationPayment::METHOD_NEQUI,
                'proof_path' => $proofPath,
                'status' => ProfileVerificationPayment::STATUS_PENDING,
                'reference_text' => $validated['reference_text'] ?? null,
            ]);

            VerificationRequest::query()->create([
                'mariachi_profile_id' => $profile->id,
                'profile_verification_payment_id' => $payment->id,
                'status' => VerificationRequest::STATUS_PENDING,
                'id_document_path' => $idDocumentPath,
                'identity_proof_path' => $identityProofPath,
                'notes' => $validated['notes'] ?? null,
                'submitted_at' => now(),
            ]);

            $profile->update([
                'verification_status' => 'payment_pending',
                'verification_notes' => null,
            ]);
        });

        return back()->with('status', 'Pago y documentos enviados. El equipo validará el comprobante y la verificación manualmente.');
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
