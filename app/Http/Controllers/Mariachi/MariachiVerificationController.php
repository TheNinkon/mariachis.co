<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MariachiVerificationController extends Controller
{
    public function edit(): View
    {
        $profile = $this->providerProfile()->loadMissing('verificationRequests.reviewedBy:id,name,first_name,last_name');
        $latestRequest = $profile->verificationRequests->first();

        return view('content.mariachi.verification', [
            'profile' => $profile,
            'latestRequest' => $latestRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();

        $validated = $request->validate([
            'id_document' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $idDocumentPath = $request->file('id_document')->store('verification-docs/id', 'public');
        $identityProofPath = $request->file('identity_proof')->store('verification-docs/proof', 'public');

        VerificationRequest::query()->create([
            'mariachi_profile_id' => $profile->id,
            'status' => VerificationRequest::STATUS_PENDING,
            'id_document_path' => $idDocumentPath,
            'identity_proof_path' => $identityProofPath,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        $profile->update([
            'verification_status' => 'pending_review',
            'verification_notes' => null,
        ]);

        return back()->with('status', 'Solicitud de verificacion enviada. El equipo la revisara manualmente.');
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->mariachiProfile()->firstOrCreate([], [
            'city_name' => 'Pendiente',
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
            'subscription_plan_code' => 'basic',
            'subscription_listing_limit' => 1,
            'subscription_active' => true,
        ]);
    }
}
