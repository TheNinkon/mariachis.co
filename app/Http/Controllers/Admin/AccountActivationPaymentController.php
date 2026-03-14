<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountActivationPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountActivationPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');

        $query = AccountActivationPayment::query()
            ->with([
                'user:id,name,first_name,last_name,email,phone,status,activation_paid_at',
                'plan:id,code,name',
                'reviewedBy:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->latest('id');

        if ($status !== 'all' && in_array($status, [
            AccountActivationPayment::STATUS_PENDING_REVIEW,
            AccountActivationPayment::STATUS_APPROVED,
            AccountActivationPayment::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $status);
        }

        $payments = $query->paginate(20)->withQueryString();

        $totals = AccountActivationPayment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('content.admin.account-activation-payments', [
            'payments' => $payments,
            'status' => $status,
            'totals' => $totals,
            'statuses' => [
                AccountActivationPayment::STATUS_PENDING_REVIEW,
                AccountActivationPayment::STATUS_APPROVED,
                AccountActivationPayment::STATUS_REJECTED,
            ],
        ]);
    }

    public function update(Request $request, AccountActivationPayment $accountActivationPayment): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => ['nullable', 'string', 'max:2000', 'required_if:action,reject'],
        ]);

        $payment = $accountActivationPayment->loadMissing('user');
        $user = $payment->user;
        abort_unless($user && $user->isMariachi(), 404);

        if ($validated['action'] === 'approve') {
            DB::transaction(function () use ($payment, $user, $request): void {
                $payment->update([
                    'status' => AccountActivationPayment::STATUS_APPROVED,
                    'reviewed_by_user_id' => $request->user()->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

                $user->update([
                    'status' => User::STATUS_ACTIVE,
                    'activation_paid_at' => now(),
                ]);
            });

            return back()->with('status', 'Pago de activacion aprobado. La cuenta ya puede iniciar sesion.');
        }

        $reason = (string) ($validated['rejection_reason'] ?? 'Comprobante rechazado por revision admin.');

        DB::transaction(function () use ($payment, $user, $request, $reason): void {
            $payment->update([
                'status' => AccountActivationPayment::STATUS_REJECTED,
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $user->update([
                'status' => User::STATUS_PENDING_ACTIVATION,
                'activation_paid_at' => null,
            ]);
        });

        return back()->with('status', 'Pago de activacion rechazado. El mariachi puede volver a intentar el cobro.');
    }
}
