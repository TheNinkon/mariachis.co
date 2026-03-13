<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileVerificationPayment;
use App\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
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
                'mariachiProfile:id,user_id,business_name,city_name,verification_status,verification_expires_at',
                'mariachiProfile.user:id,name,first_name,last_name,email',
                'payment.reviewedBy:id,name,first_name,last_name',
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
        $verificationRequest->loadMissing([
            'payment',
            'mariachiProfile',
        ]);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $now = now();
        $adminId = $request->user()->id;
        $payment = $verificationRequest->payment;
        $profile = $verificationRequest->mariachiProfile;

        if ($validated['action'] === 'approve') {
            DB::transaction(function () use ($verificationRequest, $payment, $profile, $validated, $adminId, $now): void {
                $verificationRequest->update([
                    'status' => VerificationRequest::STATUS_APPROVED,
                    'rejection_reason' => null,
                    'notes' => $validated['note'] ?? null,
                    'reviewed_by_user_id' => $adminId,
                    'reviewed_at' => $now,
                ]);

                $verificationExpiresAt = $profile?->verification_expires_at;

                if ($payment) {
                    $startsAt = $verificationExpiresAt && $verificationExpiresAt->isFuture()
                        ? $verificationExpiresAt->copy()
                        : $now->copy();
                    $endsAt = $startsAt->copy()->addMonthsNoOverflow(max(1, (int) $payment->duration_months));

                    $payment->update([
                        'status' => ProfileVerificationPayment::STATUS_APPROVED,
                        'reviewed_by_user_id' => $adminId,
                        'reviewed_at' => $now,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'rejection_reason' => null,
                    ]);

                    $verificationExpiresAt = $endsAt;
                }

                $profile?->update([
                    'verification_status' => 'verified',
                    'verification_notes' => $validated['note'] ?? null,
                    'verification_expires_at' => $verificationExpiresAt,
                ]);
            });

            return back()->with('status', 'Perfil verificado correctamente.');
        }

        $rejectionReason = $validated['rejection_reason'] ?? 'Solicitud rechazada por moderacion.';

        DB::transaction(function () use ($verificationRequest, $payment, $profile, $validated, $rejectionReason, $adminId, $now): void {
            $verificationRequest->update([
                'status' => VerificationRequest::STATUS_REJECTED,
                'rejection_reason' => $rejectionReason,
                'notes' => $validated['note'] ?? null,
                'reviewed_by_user_id' => $adminId,
                'reviewed_at' => $now,
            ]);

            if ($payment) {
                $payment->update([
                    'status' => ProfileVerificationPayment::STATUS_REJECTED,
                    'reviewed_by_user_id' => $adminId,
                    'reviewed_at' => $now,
                    'starts_at' => null,
                    'ends_at' => null,
                    'rejection_reason' => $rejectionReason,
                ]);
            }

            if ($profile && ! $profile->hasActiveVerification()) {
                $profile->update([
                    'verification_status' => 'rejected',
                    'verification_notes' => $rejectionReason,
                    'verification_expires_at' => null,
                ]);
            }
        });

        return back()->with('status', 'Solicitud de verificacion rechazada.');
    }
}
