<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WompiService
{
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_DECLINED = 'DECLINED';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_VOIDED = 'VOIDED';
    public const STATUS_PENDING = 'PENDING';

    /**
     * @return array{
     *   environment:string,
     *   public_key:string,
     *   currency:string,
     *   checkout_url:string,
     *   api_base_url:string,
     *   is_configured:bool,
     *   webhook_is_configured:bool
     * }
     */
    public function publicConfig(): array
    {
        $environment = $this->environment();
        $publicKey = trim((string) config('payments.wompi.public_key', ''));
        $integritySecret = $this->integritySecret();
        $eventsSecret = $this->eventsSecret();
        $secretsSwapped = $this->secretsLookSwapped($integritySecret, $eventsSecret);

        return [
            'environment' => $environment,
            'public_key' => $publicKey,
            'currency' => $this->currency(),
            'checkout_url' => rtrim((string) config('payments.wompi.checkout_url', 'https://checkout.wompi.co/p/'), '/').'/',
            'api_base_url' => $this->apiBaseUrl(),
            'is_configured' => $publicKey !== '' && $integritySecret !== '' && ! $secretsSwapped,
            'webhook_is_configured' => $eventsSecret !== '' && ! $secretsSwapped,
        ];
    }

    public function currency(): string
    {
        return strtoupper((string) config('payments.wompi.currency', 'COP'));
    }

    public function environment(): string
    {
        $environment = strtolower(trim((string) config('payments.wompi.environment', 'sandbox')));

        return in_array($environment, ['production', 'sandbox'], true)
            ? $environment
            : 'sandbox';
    }

    public function apiBaseUrl(): string
    {
        return rtrim((string) config(
            'payments.wompi.'.$this->environment().'_api_base_url',
            'https://sandbox.wompi.co/v1'
        ), '/');
    }

    public function checkoutUrl(array $fields): string
    {
        return $this->publicConfig()['checkout_url'].'?'.http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, scalar|null>
     */
    public function normalizedCheckoutFields(array $fields): array
    {
        return collect($fields)
            ->filter(static fn (mixed $value): bool => $value !== null && $value !== '')
            ->mapWithKeys(static fn (mixed $value, string $key): array => [
                $key => is_bool($value) ? ($value ? 'true' : 'false') : $value,
            ])
            ->all();
    }

    public function integritySignature(
        string $reference,
        int $amountInCents,
        string $currency,
        ?string $expirationTime = null
    ): string {
        $secret = $this->integritySecret();

        $payload = $reference.$amountInCents.strtoupper($currency);
        if ($expirationTime) {
            $payload .= $expirationTime;
        }

        return hash('sha256', $payload.$secret);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTransaction(string $transactionId): ?array
    {
        if ($transactionId === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->timeout(10)
            ->get($this->apiBaseUrl().'/transactions/'.urlencode($transactionId));

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data');

        return is_array($data) ? $data : null;
    }

    public function isFinalStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), [
            self::STATUS_APPROVED,
            self::STATUS_DECLINED,
            self::STATUS_ERROR,
            self::STATUS_VOIDED,
        ], true);
    }

    public function isApprovedStatus(?string $status): bool
    {
        return strtoupper((string) $status) === self::STATUS_APPROVED;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isValidEventSignature(array $payload): bool
    {
        $checksum = trim((string) Arr::get($payload, 'signature.checksum', ''));
        $properties = Arr::get($payload, 'signature.properties', []);
        $timestamp = (string) Arr::get($payload, 'timestamp', '');
        $secret = $this->eventsSecret();

        if ($checksum === '' || ! is_array($properties) || $properties === [] || $timestamp === '' || $secret === '') {
            return false;
        }

        $concatenated = collect($properties)
            ->map(function (mixed $property) use ($payload): string {
                $path = (string) $property;
                if ($path === '') {
                    return '';
                }

                $value = data_get($payload, $path);
                if ($value === null && ! Str::startsWith($path, 'data.')) {
                    $value = data_get($payload, 'data.'.$path);
                }

                if (is_array($value)) {
                    return (string) json_encode($value);
                }

                return (string) $value;
            })
            ->implode('');

        $expected = hash('sha256', $concatenated.$timestamp.$secret);

        return hash_equals($expected, $checksum);
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function rejectionReason(array $transaction): string
    {
        $status = strtoupper((string) Arr::get($transaction, 'status', ''));
        $message = trim((string) (Arr::get($transaction, 'status_message')
            ?? Arr::get($transaction, 'payment_method.extra.async_payment_url')
            ?? ''));

        if ($message !== '') {
            return $message;
        }

        return match ($status) {
            self::STATUS_DECLINED => 'Wompi reportó que la transacción fue rechazada.',
            self::STATUS_VOIDED => 'Wompi reportó que la transacción fue anulada.',
            self::STATUS_ERROR => 'Wompi reportó un error al procesar la transacción.',
            default => 'Wompi no aprobó la transacción.',
        };
    }

    private function integritySecret(): string
    {
        return trim((string) config('payments.wompi.integrity_secret', ''));
    }

    private function eventsSecret(): string
    {
        return trim((string) config('payments.wompi.events_secret', ''));
    }

    private function secretsLookSwapped(string $integritySecret, string $eventsSecret): bool
    {
        return (Str::contains($integritySecret, '_events_') && Str::contains($eventsSecret, '_integrity_'))
            || (Str::startsWith($integritySecret, 'test_events_') || Str::startsWith($integritySecret, 'prod_events_'))
            || (Str::startsWith($eventsSecret, 'test_integrity_') || Str::startsWith($eventsSecret, 'prod_integrity_'));
    }
}
