<?php

namespace App\Services;

use App\Models\ListingPayment;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\Plan;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListingPaymentService
{
    public function __construct(
        private readonly PlanAssignmentService $planAssignmentService
    ) {
    }

    /**
     * @param  array{
     *   months:int,
     *   label:string,
     *   subtotal_price_cop:int,
     *   discount_amount_cop:int,
     *   total_price_cop:int
     * }  $billingTerm
     * @return array<string, mixed>
     */
    public function previewCheckout(MariachiListing $listing, Plan $targetPlan, array $billingTerm): array
    {
        $listing->loadMissing('mariachiProfile.activeSubscription.plan');

        $currentPlan = $this->currentActivePlanSnapshot($listing);
        $baseOperation = $this->resolveBaseOperation($currentPlan, $targetPlan);
        $retryOfPayment = $this->findRetryCandidate($listing, $currentPlan, $targetPlan, $billingTerm, $baseOperation);
        $operationType = $retryOfPayment ? ListingPayment::OPERATION_RETRY : $baseOperation;
        $appliedCreditCop = $baseOperation === ListingPayment::OPERATION_UPGRADE
            ? $this->calculateUpgradeCredit($listing, $currentPlan)
            : 0;
        $baseAmountCop = max(0, (int) ($billingTerm['total_price_cop'] ?? 0));
        $finalAmountCop = max(0, $baseAmountCop - $appliedCreditCop);

        if ($finalAmountCop <= 0) {
            throw new DomainException('El saldo del cambio de plan quedó en cero. Revisa el prorrateo antes de continuar.');
        }

        $preservesCurrentPublication = in_array($baseOperation, [
            ListingPayment::OPERATION_UPGRADE,
            ListingPayment::OPERATION_RENEWAL,
        ], true) && $currentPlan !== null;

        $pendingPayment = $this->findReusablePendingPayment(
            $listing,
            $operationType,
            $retryOfPayment?->id,
            Arr::get($currentPlan, 'code'),
            $targetPlan->code,
            (int) ($billingTerm['months'] ?? 1),
            $finalAmountCop
        );

        $hasOtherPendingPayment = $listing->payments()
            ->where('status', ListingPayment::STATUS_PENDING)
            ->when($pendingPayment, fn ($query) => $query->where('id', '!=', $pendingPayment->id))
            ->exists();

        if ($hasOtherPendingPayment) {
            throw new DomainException('Ya existe otro pago pendiente para este anuncio. Resuélvelo o retoma ese checkout antes de iniciar uno nuevo.');
        }

        return [
            'operation_type' => $operationType,
            'base_operation_type' => $baseOperation,
            'retry_of_payment_id' => $retryOfPayment?->id,
            'source_plan_code' => Arr::get($currentPlan, 'code'),
            'target_plan_code' => $targetPlan->code,
            'duration_months' => (int) ($billingTerm['months'] ?? 1),
            'term_label' => (string) ($billingTerm['label'] ?? '1 mes'),
            'subtotal_amount_cop' => max(0, (int) ($billingTerm['subtotal_price_cop'] ?? $baseAmountCop)),
            'discount_amount_cop' => max(0, (int) ($billingTerm['discount_amount_cop'] ?? 0)),
            'base_amount_cop' => $baseAmountCop,
            'applied_credit_cop' => $appliedCreditCop,
            'final_amount_cop' => $finalAmountCop,
            'preserves_current_publication' => $preservesCurrentPublication,
            'existing_pending_payment_id' => $pendingPayment?->id,
            'operation_metadata' => [
                'retry_context' => $operationType === ListingPayment::OPERATION_RETRY ? $baseOperation : null,
                'requested_term_label' => $billingTerm['label'] ?? null,
                'preserves_current_publication' => $preservesCurrentPublication,
                'current_plan' => $currentPlan,
                'proration' => $this->prorationSnapshot($listing, $currentPlan, $appliedCreditCop),
            ],
            'message' => $this->checkoutMessage(
                $operationType,
                $baseOperation,
                $preservesCurrentPublication,
                $appliedCreditCop,
                $billingTerm['label'] ?? '1 mes',
                $pendingPayment !== null
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function prepareCheckout(
        MariachiListing $listing,
        MariachiProfile $profile,
        Plan $targetPlan,
        array $preview
    ): ListingPayment {
        return DB::transaction(function () use ($listing, $profile, $targetPlan, $preview): ListingPayment {
            $payment = null;
            if (! empty($preview['existing_pending_payment_id'])) {
                $payment = ListingPayment::query()->find($preview['existing_pending_payment_id']);
            }

            if (! $payment) {
                $payment = $listing->payments()->create([
                    'mariachi_profile_id' => $profile->id,
                    'plan_code' => $targetPlan->code,
                    'duration_months' => (int) $preview['duration_months'],
                    'amount_cop' => (int) $preview['final_amount_cop'],
                    'method' => ListingPayment::METHOD_WOMPI,
                    'checkout_reference' => null,
                    'proof_path' => null,
                    'status' => ListingPayment::STATUS_PENDING,
                    'operation_type' => (string) $preview['operation_type'],
                    'retry_of_payment_id' => $preview['retry_of_payment_id'],
                    'source_plan_code' => $preview['source_plan_code'],
                    'target_plan_code' => $targetPlan->code,
                    'subtotal_amount_cop' => (int) $preview['subtotal_amount_cop'],
                    'discount_amount_cop' => (int) $preview['discount_amount_cop'],
                    'base_amount_cop' => (int) $preview['base_amount_cop'],
                    'applied_credit_cop' => (int) $preview['applied_credit_cop'],
                    'final_amount_cop' => (int) $preview['final_amount_cop'],
                    'operation_metadata' => $preview['operation_metadata'],
                ]);
            }

            $this->syncListingForPendingPayment($listing, $targetPlan, $preview);

            return $payment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $paymentAttributes
     */
    public function approvePayment(ListingPayment $payment, ?int $reviewerId = null, array $paymentAttributes = []): void
    {
        $payment->loadMissing('listing.mariachiProfile.activeSubscription.plan');

        $listing = $payment->listing;
        if (! $listing) {
            throw new DomainException('El pago no tiene un anuncio asociado.');
        }

        $profile = $listing->mariachiProfile;
        if (! $profile) {
            throw new DomainException('El anuncio no tiene un perfil asociado.');
        }

        $targetPlanCode = $payment->targetPlanCode();
        $targetPlan = Plan::query()->where('code', $targetPlanCode)->first();
        if (! $targetPlan) {
            throw new DomainException('No encontramos el plan asociado a este pago.');
        }

        $effectiveOperation = $payment->effectiveOperationType();
        $reviewedAt = $paymentAttributes['reviewed_at'] ?? now();

        $payment->fill(array_merge($paymentAttributes, [
            'plan_code' => $targetPlanCode,
            'method' => $payment->method ?: ListingPayment::METHOD_WOMPI,
            'status' => ListingPayment::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $reviewedAt,
            'rejection_reason' => null,
        ]));
        $payment->save();

        if (in_array($effectiveOperation, [ListingPayment::OPERATION_UPGRADE, ListingPayment::OPERATION_RENEWAL], true)) {
            $this->applyApprovedPlanChange($payment, $listing, $profile, $targetPlan, $effectiveOperation, $reviewerId);

            return;
        }

        $listing->update([
            'selected_plan_code' => $targetPlanCode,
            'plan_duration_months' => max(1, (int) ($payment->duration_months ?: 1)),
            'plan_selected_at' => $listing->plan_selected_at ?? $payment->created_at ?? now(),
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'status' => MariachiListing::STATUS_DRAFT,
            'is_active' => false,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
            'deactivated_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $paymentAttributes
     */
    public function rejectPayment(ListingPayment $payment, string $reason, ?int $reviewerId = null, array $paymentAttributes = []): void
    {
        $payment->loadMissing('listing');

        $listing = $payment->listing;
        if (! $listing) {
            throw new DomainException('El pago no tiene un anuncio asociado.');
        }

        $reviewedAt = $paymentAttributes['reviewed_at'] ?? now();

        $payment->fill(array_merge($paymentAttributes, [
            'status' => ListingPayment::STATUS_REJECTED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $reviewedAt,
            'rejection_reason' => $reason,
        ]));
        $payment->save();

        $preservesCurrentPublication = (bool) data_get($payment->operation_metadata, 'preserves_current_publication', false);
        if ($preservesCurrentPublication) {
            return;
        }

        $listing->update([
            'payment_status' => MariachiListing::PAYMENT_REJECTED,
            'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
            'is_active' => false,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentActivePlanSnapshot(MariachiListing $listing): ?array
    {
        if (
            $listing->status !== MariachiListing::STATUS_ACTIVE
            || ! $listing->is_active
            || $listing->review_status !== MariachiListing::REVIEW_APPROVED
        ) {
            return null;
        }

        $latestApprovedPayment = $listing->payments()
            ->where('status', ListingPayment::STATUS_APPROVED)
            ->latest('id')
            ->first();

        $plan = $listing->mariachiProfile?->activeSubscription?->plan
            ?? ($latestApprovedPayment?->targetPlanCode()
                ? Plan::query()->where('code', $latestApprovedPayment->targetPlanCode())->first()
                : null);

        $planCode = $listing->selected_plan_code ?: $plan?->code;
        if (! $planCode) {
            return null;
        }

        $periodEndsAt = $listing->plan_expires_at
            ?: $listing->mariachiProfile?->activeSubscription?->renews_at
            ?: $listing->mariachiProfile?->activeSubscription?->ends_at;

        if ($periodEndsAt && $periodEndsAt->isPast()) {
            return null;
        }

        return [
            'code' => $planCode,
            'duration_months' => (int) ($listing->plan_duration_months ?: $latestApprovedPayment?->duration_months ?: 1),
            'activated_at' => $listing->activated_at?->toIso8601String(),
            'plan_expires_at' => $periodEndsAt?->toIso8601String(),
            'last_paid_amount_cop' => (int) ($latestApprovedPayment?->chargedAmountCop()
                ?: $listing->mariachiProfile?->activeSubscription?->base_amount_cop
                ?: (($plan?->price_cop ?: 0) * max(1, (int) ($listing->plan_duration_months ?: 1)))),
            'priority_level' => (int) ($plan?->priority_level ?? 0),
            'payment_id' => $latestApprovedPayment?->id,
            'payment_reviewed_at' => $latestApprovedPayment?->reviewed_at?->toIso8601String(),
        ];
    }

    private function resolveBaseOperation(?array $currentPlan, Plan $targetPlan): string
    {
        if ($currentPlan === null) {
            return ListingPayment::OPERATION_INITIAL;
        }

        if ((string) $currentPlan['code'] === $targetPlan->code) {
            return ListingPayment::OPERATION_RENEWAL;
        }

        $currentRank = ((int) ($currentPlan['priority_level'] ?? 0) * 1000000) + (int) ($currentPlan['last_paid_amount_cop'] ?? 0);
        $targetRank = ((int) $targetPlan->priority_level * 1000000) + (int) $targetPlan->price_cop;

        if ($targetRank <= $currentRank) {
            throw new DomainException('Ese cambio no es un upgrade válido. Mantén el plan actual o espera al vencimiento para cambiarlo.');
        }

        return ListingPayment::OPERATION_UPGRADE;
    }

    /**
     * @param  array<string, mixed>|null  $currentPlan
     * @param  array<string, mixed>  $billingTerm
     */
    private function findRetryCandidate(
        MariachiListing $listing,
        ?array $currentPlan,
        Plan $targetPlan,
        array $billingTerm,
        string $baseOperation
    ): ?ListingPayment {
        return $listing->payments()
            ->where('status', ListingPayment::STATUS_REJECTED)
            ->latest('id')
            ->get()
            ->first(function (ListingPayment $payment) use ($currentPlan, $targetPlan, $billingTerm, $baseOperation): bool {
                $retryContext = data_get($payment->operation_metadata, 'retry_context');
                $retryContext = is_string($retryContext) && $retryContext !== ''
                    ? $retryContext
                    : $payment->effectiveOperationType();

                return $payment->targetPlanCode() === $targetPlan->code
                    && (int) $payment->duration_months === (int) ($billingTerm['months'] ?? 1)
                    && (string) ($payment->source_plan_code ?: '') === (string) ($currentPlan['code'] ?? '')
                    && $retryContext === $baseOperation;
            });
    }

    private function calculateUpgradeCredit(MariachiListing $listing, ?array $currentPlan): int
    {
        if ($currentPlan === null) {
            return 0;
        }

        $periodEndsAt = filled($currentPlan['plan_expires_at'])
            ? Carbon::parse((string) $currentPlan['plan_expires_at'])
            : null;

        if (! $periodEndsAt || $periodEndsAt->isPast()) {
            return 0;
        }

        $startsAt = filled($currentPlan['payment_reviewed_at'])
            ? Carbon::parse((string) $currentPlan['payment_reviewed_at'])
            : ($listing->activated_at ?: $periodEndsAt->copy()->subMonthsNoOverflow(max(1, (int) ($currentPlan['duration_months'] ?? 1))));

        $totalSeconds = max(1, $startsAt->diffInSeconds($periodEndsAt, false));
        $remainingSeconds = max(0, now()->diffInSeconds($periodEndsAt, false));
        $basisAmount = max(0, (int) ($currentPlan['last_paid_amount_cop'] ?? 0));

        if ($basisAmount === 0 || $remainingSeconds === 0) {
            return 0;
        }

        return min($basisAmount, (int) round($basisAmount * ($remainingSeconds / $totalSeconds)));
    }

    /**
     * @param  array<string, mixed>|null  $currentPlan
     * @return array<string, mixed>
     */
    private function prorationSnapshot(MariachiListing $listing, ?array $currentPlan, int $appliedCreditCop): array
    {
        if ($currentPlan === null) {
            return [];
        }

        $periodEndsAt = filled($currentPlan['plan_expires_at'])
            ? Carbon::parse((string) $currentPlan['plan_expires_at'])
            : null;

        if (! $periodEndsAt) {
            return [];
        }

        $startsAt = filled($currentPlan['payment_reviewed_at'])
            ? Carbon::parse((string) $currentPlan['payment_reviewed_at'])
            : ($listing->activated_at ?: null);

        return [
            'starts_at' => $startsAt?->toIso8601String(),
            'ends_at' => $periodEndsAt->toIso8601String(),
            'days_remaining' => max(0, now()->diffInDays($periodEndsAt, false)),
            'basis_amount_cop' => (int) ($currentPlan['last_paid_amount_cop'] ?? 0),
            'applied_credit_cop' => $appliedCreditCop,
        ];
    }

    private function findReusablePendingPayment(
        MariachiListing $listing,
        string $operationType,
        ?int $retryOfPaymentId,
        ?string $sourcePlanCode,
        string $targetPlanCode,
        int $durationMonths,
        int $finalAmountCop
    ): ?ListingPayment {
        return $listing->payments()
            ->where('status', ListingPayment::STATUS_PENDING)
            ->latest('id')
            ->get()
            ->first(function (ListingPayment $payment) use (
                $operationType,
                $retryOfPaymentId,
                $sourcePlanCode,
                $targetPlanCode,
                $durationMonths,
                $finalAmountCop
            ): bool {
                return $payment->operation_type === $operationType
                    && (int) ($payment->retry_of_payment_id ?: 0) === (int) ($retryOfPaymentId ?: 0)
                    && (string) ($payment->source_plan_code ?: '') === (string) ($sourcePlanCode ?: '')
                    && $payment->targetPlanCode() === $targetPlanCode
                    && (int) $payment->duration_months === $durationMonths
                    && $payment->chargedAmountCop() === $finalAmountCop;
            });
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    private function syncListingForPendingPayment(MariachiListing $listing, Plan $targetPlan, array $preview): void
    {
        if ((bool) ($preview['preserves_current_publication'] ?? false)) {
            return;
        }

        $listing->update([
            'selected_plan_code' => $targetPlan->code,
            'plan_duration_months' => (int) $preview['duration_months'],
            'plan_selected_at' => now(),
            'payment_status' => MariachiListing::PAYMENT_PENDING,
            'status' => MariachiListing::STATUS_AWAITING_PAYMENT,
            'is_active' => false,
            'review_status' => MariachiListing::REVIEW_DRAFT,
            'submitted_for_review_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'rejection_reason' => null,
            'deactivated_at' => now(),
        ]);
    }

    private function applyApprovedPlanChange(
        ListingPayment $payment,
        MariachiListing $listing,
        MariachiProfile $profile,
        Plan $targetPlan,
        string $effectiveOperation,
        ?int $reviewerId
    ): void {
        $durationMonths = max(1, (int) ($payment->duration_months ?: 1));
        $currentPlanEndsAt = $listing->plan_expires_at ?: $profile->activeSubscription?->renews_at ?: now();
        $effectiveStartAt = $effectiveOperation === ListingPayment::OPERATION_RENEWAL && $currentPlanEndsAt && $currentPlanEndsAt->isFuture()
            ? $currentPlanEndsAt
            : now();

        $this->planAssignmentService->assignToProfile(
            $profile,
            $targetPlan,
            $listing,
            $effectiveOperation === ListingPayment::OPERATION_UPGRADE ? 'listing_upgrade_payment' : 'listing_renewal_payment',
            [
                'listing_payment_id' => $payment->id,
                'operation_type' => $payment->operation_type,
                'effective_operation_type' => $effectiveOperation,
                'reviewed_by_user_id' => $reviewerId,
                'method' => $payment->method,
                'duration_months' => $durationMonths,
                'source_plan_code' => $payment->source_plan_code,
                'target_plan_code' => $payment->targetPlanCode(),
                'provider_transaction_id' => $payment->provider_transaction_id,
                'applied_credit_cop' => (int) ($payment->applied_credit_cop ?: 0),
            ],
            true,
            $durationMonths,
            $payment->chargedAmountCop(),
            $effectiveStartAt
        );

        $listing->update([
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'review_status' => $listing->review_status,
            'status' => $listing->status,
            'is_active' => $listing->is_active,
            'submitted_for_review_at' => $listing->submitted_for_review_at,
            'reviewed_at' => $listing->reviewed_at,
            'reviewed_by_user_id' => $listing->reviewed_by_user_id,
            'rejection_reason' => $listing->rejection_reason,
            'deactivated_at' => $listing->deactivated_at,
        ]);
    }

    private function checkoutMessage(
        string $operationType,
        string $baseOperation,
        bool $preservesCurrentPublication,
        int $appliedCreditCop,
        string $termLabel,
        bool $reusesPendingPayment
    ): string {
        if ($reusesPendingPayment) {
            return 'Ya existe un checkout Wompi pendiente para este '.$termLabel.'. Puedes retomarlo sin crear un nuevo intento.';
        }

        if ($operationType === ListingPayment::OPERATION_RETRY) {
            return $preservesCurrentPublication
                ? 'Se preparó un reintento de pago sin tocar tu anuncio activo.'
                : 'Se preparó un reintento de pago para este anuncio.';
        }

        if ($baseOperation === ListingPayment::OPERATION_RENEWAL) {
            return 'Se preparó una renovación. El anuncio seguirá activo y el nuevo periodo se aplicará cuando el pago quede aprobado.';
        }

        if ($baseOperation === ListingPayment::OPERATION_UPGRADE) {
            if ($appliedCreditCop > 0) {
                return 'Se preparó un upgrade sin desactivar el anuncio. Se aplicará un crédito por tiempo restante de $'.number_format($appliedCreditCop, 0, ',', '.').' COP.';
            }

            return 'Se preparó un upgrade sin desactivar el anuncio actual.';
        }

        return 'Plan seleccionado. Ahora serás redirigido a Wompi para completar el pago.';
    }
}
