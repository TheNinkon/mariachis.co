<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');

        $query = VerificationRequest::query()
            ->with([
                'mariachiProfile:id,user_id,business_name,city_name,verification_status',
                'mariachiProfile.user:id,name,first_name,last_name,email',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->latest('submitted_at')
            ->latest('id');

        if ($status !== 'all' && in_array($status, [
            VerificationRequest::STATUS_PENDING,
            VerificationRequest::STATUS_APPROVED,
            VerificationRequest::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        $totals = VerificationRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('content.admin.profile-verifications', [
            'requests' => $requests,
            'status' => $status,
            'totals' => $totals,
            'statuses' => [
                VerificationRequest::STATUS_PENDING,
                VerificationRequest::STATUS_APPROVED,
                VerificationRequest::STATUS_REJECTED,
            ],
        ]);
    }

    public function update(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $now = now();
        $adminId = $request->user()->id;

        if ($validated['action'] === 'approve') {
            $verificationRequest->update([
                'status' => VerificationRequest::STATUS_APPROVED,
                'rejection_reason' => null,
                'notes' => $validated['note'] ?? null,
                'reviewed_by_user_id' => $adminId,
                'reviewed_at' => $now,
            ]);

            $verificationRequest->mariachiProfile?->update([
                'verification_status' => 'verified',
                'verification_notes' => $validated['note'] ?? null,
            ]);

            return back()->with('status', 'Perfil verificado correctamente.');
        }

        $rejectionReason = $validated['rejection_reason'] ?? 'Solicitud rechazada por moderacion.';

        $verificationRequest->update([
            'status' => VerificationRequest::STATUS_REJECTED,
            'rejection_reason' => $rejectionReason,
            'notes' => $validated['note'] ?? null,
            'reviewed_by_user_id' => $adminId,
            'reviewed_at' => $now,
        ]);

        $verificationRequest->mariachiProfile?->update([
            'verification_status' => 'rejected',
            'verification_notes' => $rejectionReason,
        ]);

        return back()->with('status', 'Solicitud de verificacion rechazada.');
    }
}
