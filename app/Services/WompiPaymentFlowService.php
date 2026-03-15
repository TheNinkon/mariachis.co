<?php

namespace App\Services;

use App\Models\AccountActivationPayment;
use App\Models\AccountActivationPlan;
use App\Models\ListingPayment;
use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\ProfileVerificationPayment;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WompiPaymentFlowService
{
    public const TYPE_ACTIVATION = 'activation';
    public const TYPE_LISTING = 'listing';
    public const TYPE_VERIFICATION = 'verification';

    public function __construct(
        private readonly WompiService $wompi,
        private readonly ListingPaymentService $listingPaymentService
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->wompi->publicConfig()['is_configured'];
    }

    /**
     * @return array{type:string,payment:Model}|null
     */
    public function findPaymentByReference(string $reference): ?array
    {
        $payment = AccountActivationPayment::query()->where('checkout_reference', $reference)->first();
        if ($payment) {
            return ['type' => self::TYPE_ACTIVATION, 'payment' => $payment];
        }

        $payment = ListingPayment::query()->where('checkout_reference', $reference)->first();
        if ($payment) {
            return ['type' => self::TYPE_LISTING, 'payment' => $payment];
        }

        $payment = ProfileVerificationPayment::query()->where('checkout_reference', $reference)->first();
        if ($payment) {
            return ['type' => self::TYPE_VERIFICATION, 'payment' => $payment];
        }

        return null;
    }

    public function findPayment(string $type, string $reference): ?Model
    {
        return match ($type) {
            self::TYPE_ACTIVATION => AccountActivationPayment::query()->where('checkout_reference', $reference)->first(),
            self::TYPE_LISTING => ListingPayment::query()->where('checkout_reference', $reference)->first(),
            self::TYPE_VERIFICATION => ProfileVerificationPayment::query()->where('checkout_reference', $reference)->first(),
            default => null,
        };
    }

    public function beginActivationCheckout(User $user, AccountActivationPlan $plan): string
    {
        $payment = $user->activationPayments()
            ->latest('id')
            ->first();

        if (! $payment || ! $payment->isPending()) {
            $payment = AccountActivationPayment::query()->create([
                'user_id' => $user->id,
                'account_activation_plan_id' => $plan->id,
                'amount_cop' => (int) $plan->amount_cop,
                'method' => AccountActivationPayment::METHOD_WOMPI,
                'proof_path' => null,
                'status' => AccountActivationPayment::STATUS_PENDING_REVIEW,
            ]);
        }

        return $this->checkoutUrl(self::TYPE_ACTIVATION, $payment, [
            'email' => $user->email,
            'full-name' => $user->display_name,
        ] + $this->customerPhoneData($user));
    }

    public function beginListingCheckout(
        MariachiListing $listing,
        MariachiProfile $profile,
        string $planCode,
        int $durationMonths,
        int $amountCop
    ): string {
        $payment = $listing->payments()
            ->where('status', ListingPayment::STATUS_PENDING)
            ->where('target_plan_code', $planCode)
            ->where('duration_months', $durationMonths)
            ->where(function ($query) use ($amountCop): void {
                $query->where('final_amount_cop', $amountCop)
                    ->orWhere(function ($legacyQuery) use ($amountCop): void {
                        $legacyQuery->whereNull('final_amount_cop')
                            ->where('amount_cop', $amountCop);
                    });
            })
            ->latest('id')
            ->first();

        if (! $payment) {
            throw new InvalidArgumentException('Listing checkout requires a prepared pending payment.');
        }

        return $this->checkoutUrlForListingPayment($payment, $profile);
    }

    public function checkoutUrlForListingPayment(ListingPayment $payment, MariachiProfile $profile): string
    {
        return $this->checkoutUrl(self::TYPE_LISTING, $payment, $this->customerDataForProfile($profile));
    }

    /**
     * @param  array{notes:?string,id_document_path:string,identity_proof_path:string}  $requestData
     * @param  array{code:string,duration_months:int,amount_cop:int}  $plan
     */
    public function beginVerificationCheckout(
        MariachiProfile $profile,
        array $plan,
        array $requestData
    ): string {
        $payment = $profile->verificationPayments()
            ->latest('id')
            ->first();
        $verificationRequest = $profile->verificationRequests()
            ->latest('id')
            ->first();

        if (! $payment || ! $payment->isPending() || ! $verificationRequest || $verificationRequest->status !== VerificationRequest::STATUS_PENDING) {
            $payment = ProfileVerificationPayment::query()->create([
                'mariachi_profile_id' => $profile->id,
                'plan_code' => $plan['code'],
                'duration_months' => (int) $plan['duration_months'],
                'amount_cop' => (int) $plan['amount_cop'],
                'method' => ProfileVerificationPayment::METHOD_WOMPI,
                'proof_path' => null,
                'status' => ProfileVerificationPayment::STATUS_PENDING,
            ]);

            $verificationRequest = VerificationRequest::query()->create([
                'mariachi_profile_id' => $profile->id,
                'profile_verification_payment_id' => $payment->id,
                'status' => VerificationRequest::STATUS_PENDING,
                'id_document_path' => $requestData['id_document_path'],
                'identity_proof_path' => $requestData['identity_proof_path'],
                'notes' => $requestData['notes'],
                'submitted_at' => now(),
            ]);
        } else {
            $verificationRequest->update([
                'id_document_path' => $requestData['id_document_path'],
                'identity_proof_path' => $requestData['identity_proof_path'],
                'notes' => $requestData['notes'],
                'rejection_reason' => null,
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
            ]);
        }

        $profile->update([
            'verification_status' => 'payment_pending',
            'verification_notes' => null,
        ]);

        return $this->checkoutUrl(self::TYPE_VERIFICATION, $payment, $this->customerDataForProfile($profile));
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function syncPaymentFromTransaction(string $type, Model $payment, array $transaction): void
    {
        $reference = (string) ($transaction['reference'] ?? '');
        $transactionId = (string) ($transaction['id'] ?? '');
        $transactionStatus = strtoupper((string) ($transaction['status'] ?? ''));
        $finalizedAt = now();
        $encodedPayload = $this->providerPayload($transaction);

        if ($reference === '' || $transactionId === '' || ! $this->wompi->isFinalStatus($transactionStatus)) {
            return;
        }

        if ((string) $payment->getAttribute('checkout_reference') !== $reference) {
            return;
        }

        $rejectionReason = $this->wompi->rejectionReason($transaction);

        DB::transaction(function () use (
            $type,
            $payment,
            $transactionId,
            $transactionStatus,
            $encodedPayload,
            $rejectionReason,
            $finalizedAt
        ): void {
            match ($type) {
                self::TYPE_ACTIVATION => $this->syncActivationPayment(
                    $payment,
                    $transactionId,
                    $transactionStatus,
                    $encodedPayload,
                    $rejectionReason,
                    $finalizedAt
                ),
                self::TYPE_LISTING => $this->syncListingPayment(
                    $payment,
                    $transactionId,
                    $transactionStatus,
                    $encodedPayload,
                    $rejectionReason,
                    $finalizedAt
                ),
                self::TYPE_VERIFICATION => $this->syncVerificationPayment(
                    $payment,
                    $transactionId,
                    $transactionStatus,
                    $encodedPayload,
                    $rejectionReason,
                    $finalizedAt
                ),
                default => throw new InvalidArgumentException('Unsupported Wompi payment type ['.$type.'].'),
            };
        });
    }

    /**
     * @param  array<string, string>  $customerData
     */
    private function checkoutUrl(string $type, Model $payment, array $customerData = []): string
    {
        $amountInCents = ((int) $payment->getAttribute('amount_cop')) * 100;
        $reference = (string) $payment->getAttribute('checkout_reference');

        if ($reference === '') {
            $reference = $this->generateReference($type, (int) $payment->getKey());
            $payment->forceFill(['checkout_reference' => $reference])->save();
        }

        $fields = [
            'public-key' => $this->wompi->publicConfig()['public_key'],
            'currency' => $this->wompi->currency(),
            'amount-in-cents' => $amountInCents,
            'reference' => $reference,
            'signature:integrity' => $this->wompi->integritySignature($reference, $amountInCents, $this->wompi->currency()),
            'redirect-url' => route('mariachi.wompi.redirect', [
                'type' => $type,
                'reference' => $reference,
            ]),
        ];

        foreach ($customerData as $key => $value) {
            $fields['customer-data:'.$key] = $value;
        }

        return $this->wompi->checkoutUrl($this->wompi->normalizedCheckoutFields($fields));
    }

    private function generateReference(string $type, int $paymentId): string
    {
        $prefix = match ($type) {
            self::TYPE_ACTIVATION => 'ACT',
            self::TYPE_LISTING => 'LST',
            self::TYPE_VERIFICATION => 'VER',
            default => 'PAY',
        };

        return $prefix.'-'.$paymentId.'-'.Str::upper(Str::random(10));
    }

    /**
     * @return array<string, string>
     */
    private function customerDataForProfile(MariachiProfile $profile): array
    {
        $user = $profile->user;

        if (! $user) {
            return [];
        }

        return [
            'email' => $user->email,
            'full-name' => $user->display_name,
        ] + $this->customerPhoneData($user);
    }

    /**
     * @return array<string, string>
     */
    private function customerPhoneData(User $user): array
    {
        $phone = trim((string) $user->phone);
        if ($phone === '') {
            return [];
        }

        if (preg_match('/^\+?(\d+)\s+(.+)$/', $phone, $matches) === 1) {
            $prefix = '+'.$matches[1];
            $number = preg_replace('/\D+/', '', $matches[2]) ?: '';

            if ($number !== '') {
                return [
                    'phone-number-prefix' => $prefix,
                    'phone-number' => $number,
                ];
            }
        }

        $normalized = preg_replace('/\D+/', '', $phone) ?: '';

        return $normalized !== ''
            ? [
                'phone-number-prefix' => '+57',
                'phone-number' => $normalized,
            ]
            : [];
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function providerPayload(array $transaction): array
    {
        return [
            'id' => $transaction['id'] ?? null,
            'status' => $transaction['status'] ?? null,
            'reference' => $transaction['reference'] ?? null,
            'status_message' => $transaction['status_message'] ?? null,
            'amount_in_cents' => $transaction['amount_in_cents'] ?? null,
            'currency' => $transaction['currency'] ?? null,
            'payment_method_type' => $transaction['payment_method_type'] ?? null,
            'finalized_at' => $transaction['finalized_at'] ?? null,
        ];
    }

    private function syncActivationPayment(
        Model $paymentModel,
        string $transactionId,
        string $transactionStatus,
        array $providerPayload,
        string $rejectionReason,
        $finalizedAt
    ): void {
        /** @var AccountActivationPayment $payment */
        $payment = $paymentModel->loadMissing('user');
        $user = $payment->user;

        if (! $user) {
            return;
        }

        $approved = $this->wompi->isApprovedStatus($transactionStatus);

        $payment->update([
            'method' => AccountActivationPayment::METHOD_WOMPI,
            'status' => $approved ? AccountActivationPayment::STATUS_APPROVED : AccountActivationPayment::STATUS_REJECTED,
            'provider_transaction_id' => $transactionId,
            'provider_transaction_status' => $transactionStatus,
            'provider_payload' => $providerPayload,
            'reviewed_at' => $finalizedAt,
            'reviewed_by_user_id' => null,
            'rejection_reason' => $approved ? null : $rejectionReason,
        ]);

        $user->update([
            'status' => $approved ? User::STATUS_ACTIVE : User::STATUS_PENDING_ACTIVATION,
            'activation_paid_at' => $approved ? $finalizedAt : null,
        ]);
    }

    private function syncListingPayment(
        Model $paymentModel,
        string $transactionId,
        string $transactionStatus,
        array $providerPayload,
        string $rejectionReason,
        $finalizedAt
    ): void {
        $approved = $this->wompi->isApprovedStatus($transactionStatus);

        /** @var ListingPayment $payment */
        $payment = $paymentModel instanceof ListingPayment ? $paymentModel : ListingPayment::query()->findOrFail($paymentModel->getKey());

        $attributes = [
            'method' => ListingPayment::METHOD_WOMPI,
            'provider_transaction_id' => $transactionId,
            'provider_transaction_status' => $transactionStatus,
            'provider_payload' => $providerPayload,
            'reviewed_at' => $finalizedAt,
        ];

        if ($approved) {
            $this->listingPaymentService->approvePayment($payment, null, $attributes);

            return;
        }

        $this->listingPaymentService->rejectPayment($payment, $rejectionReason, null, $attributes);
    }

    private function syncVerificationPayment(
        Model $paymentModel,
        string $transactionId,
        string $transactionStatus,
        array $providerPayload,
        string $rejectionReason,
        $finalizedAt
    ): void {
        /** @var ProfileVerificationPayment $payment */
        $payment = $paymentModel->loadMissing(['mariachiProfile', 'verificationRequest']);
        $profile = $payment->mariachiProfile;
        $request = $payment->verificationRequest;

        if (! $profile) {
            return;
        }

        $approved = $this->wompi->isApprovedStatus($transactionStatus);

        $payment->update([
            'method' => ProfileVerificationPayment::METHOD_WOMPI,
            'status' => $approved ? ProfileVerificationPayment::STATUS_APPROVED : ProfileVerificationPayment::STATUS_REJECTED,
            'provider_transaction_id' => $transactionId,
            'provider_transaction_status' => $transactionStatus,
            'provider_payload' => $providerPayload,
            'reviewed_at' => $finalizedAt,
            'reviewed_by_user_id' => null,
            'starts_at' => null,
            'ends_at' => null,
            'rejection_reason' => $approved ? null : $rejectionReason,
        ]);

        if ($request) {
            $request->update([
                'status' => $approved ? VerificationRequest::STATUS_PENDING : VerificationRequest::STATUS_REJECTED,
                'rejection_reason' => $approved ? null : $rejectionReason,
                'reviewed_by_user_id' => null,
                'reviewed_at' => $approved ? null : $finalizedAt,
            ]);
        }

        $profile->update([
            'verification_status' => $approved ? 'payment_pending' : 'rejected',
            'verification_notes' => $approved ? null : $rejectionReason,
            'verification_expires_at' => $approved ? $profile->verification_expires_at : null,
        ]);
    }
}
