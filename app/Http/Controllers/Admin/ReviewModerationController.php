<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MariachiReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewModerationController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = (string) $request->query('status', 'all');
        $verificationFilter = (string) $request->query('verification', 'all');

        $reviewsQuery = MariachiReview::query()
            ->with([
                'clientUser:id,name,first_name,last_name,email',
                'mariachiProfile:id,user_id,business_name,slug',
                'mariachiProfile.user:id,name,first_name,last_name',
                'photos',
                'moderatedBy:id,name,first_name,last_name',
                'reportedBy:id,name,first_name,last_name',
            ]);

        if ($statusFilter !== 'all' && in_array($statusFilter, MariachiReview::MODERATION_STATUSES, true)) {
            $reviewsQuery->where('moderation_status', $statusFilter);
        }

        if ($verificationFilter !== 'all' && in_array($verificationFilter, MariachiReview::VERIFICATION_STATUSES, true)) {
            $reviewsQuery->where('verification_status', $verificationFilter);
        }

        $reviews = $reviewsQuery
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $statusTotals = MariachiReview::query()
            ->selectRaw('moderation_status, count(*) as total')
            ->groupBy('moderation_status')
            ->pluck('total', 'moderation_status');

        $verificationTotals = MariachiReview::query()
            ->selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return view('content.admin.reviews-index', [
            'reviews' => $reviews,
            'statusFilter' => $statusFilter,
            'verificationFilter' => $verificationFilter,
            'statusTotals' => $statusTotals,
            'verificationTotals' => $verificationTotals,
            'statuses' => MariachiReview::MODERATION_STATUSES,
            'verificationStatuses' => MariachiReview::VERIFICATION_STATUSES,
        ]);
    }

    public function moderate(Request $request, MariachiReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'hide', 'spam'])],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $action = $validated['action'];
        $reason = $validated['reason'] ?? null;
        $now = now();
        $adminId = $request->user()->id;

        if ($action === 'approve') {
            $verificationStatus = $review->verification_status;
            if ($verificationStatus === MariachiReview::VERIFICATION_BASIC && $review->photos()->exists()) {
                $verificationStatus = MariachiReview::VERIFICATION_WITH_EVIDENCE;
            }

            $review->update([
                'moderation_status' => MariachiReview::STATUS_APPROVED,
                'is_visible' => true,
                'is_spam' => false,
                'rejection_reason' => null,
                'moderated_by_user_id' => $adminId,
                'moderated_at' => $now,
                'verification_status' => $verificationStatus,
            ]);

            return back()->with('status', 'Resena aprobada y visible publicamente.');
        }

        if ($action === 'reject') {
            $review->update([
                'moderation_status' => MariachiReview::STATUS_REJECTED,
                'is_visible' => false,
                'rejection_reason' => $reason,
                'moderated_by_user_id' => $adminId,
                'moderated_at' => $now,
            ]);

            return back()->with('status', 'Resena rechazada.');
        }

        if ($action === 'hide') {
            $review->update([
                'moderation_status' => MariachiReview::STATUS_HIDDEN,
                'is_visible' => false,
                'moderated_by_user_id' => $adminId,
                'moderated_at' => $now,
            ]);

            return back()->with('status', 'Resena oculta.');
        }

        $review->increment('reports_count');

        $review->update([
            'moderation_status' => MariachiReview::STATUS_REPORTED,
            'is_visible' => false,
            'is_spam' => true,
            'latest_report_reason' => $reason ?: 'Marcada como spam por moderacion.',
            'reported_at' => $now,
            'reported_by_user_id' => $adminId,
            'moderated_by_user_id' => $adminId,
            'moderated_at' => $now,
        ]);

        return back()->with('status', 'Resena marcada como spam.');
    }

    public function updateVerification(Request $request, MariachiReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in(MariachiReview::VERIFICATION_STATUSES)],
        ]);

        $review->update([
            'verification_status' => $validated['verification_status'],
            'moderated_by_user_id' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('status', 'Estado de verificacion actualizado.');
    }

    public function moderateReply(Request $request, MariachiReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'reply_visible' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update([
            'mariachi_reply_visible' => (bool) $validated['reply_visible'],
            'mariachi_reply_moderation_note' => $validated['note'] ?? null,
            'mariachi_reply_moderated_by_user_id' => $request->user()->id,
            'mariachi_reply_moderated_at' => now(),
        ]);

        return back()->with('status', 'Moderacion de respuesta actualizada.');
    }
}
