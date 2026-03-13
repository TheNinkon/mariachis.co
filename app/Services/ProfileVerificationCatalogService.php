<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProfileVerificationCatalogService
{
    private const BASE_PRICE_COP = 18900;

    /**
     * @return array<string, array{
     *   code:string,
     *   name:string,
     *   duration_months:int,
     *   amount_cop:int,
     *   description:string
     * }>
     */
    public function plans(): array
    {
        return [
            'verification-1m' => [
                'code' => 'verification-1m',
                'name' => '1 mes',
                'duration_months' => 1,
                'amount_cop' => self::BASE_PRICE_COP,
                'description' => 'Verificación premium por 1 mes para habilitar insignia y handle personalizado.',
            ],
            'verification-3m' => [
                'code' => 'verification-3m',
                'name' => '3 meses',
                'duration_months' => 3,
                'amount_cop' => self::BASE_PRICE_COP * 3,
                'description' => 'Extiende la verificación premium durante 3 meses continuos.',
            ],
            'verification-12m' => [
                'code' => 'verification-12m',
                'name' => '12 meses',
                'duration_months' => 12,
                'amount_cop' => self::BASE_PRICE_COP * 12,
                'description' => 'Mantén la insignia y el handle premium durante 12 meses.',
            ],
        ];
    }

    /**
     * @return array{
     *   code:string,
     *   name:string,
     *   duration_months:int,
     *   amount_cop:int,
     *   description:string
     * }|null
     */
    public function plan(string $code): ?array
    {
        return Arr::get($this->plans(), $code);
    }

    /**
     * @return list<string>
     */
    public function reservedHandles(): array
    {
        return collect(config('seo.reserved_slugs', []))
            ->merge([
                'api',
                'partner',
                'signup',
                'register',
                'forgot-password',
                'reset-password',
                'verificacion',
                'verification',
                'security',
                'notifications',
                'billing',
                'planes',
                'cuenta',
            ])
            ->map(fn (mixed $value): string => Str::slug((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isReservedHandle(string $handle): bool
    {
        return in_array(Str::slug($handle), $this->reservedHandles(), true);
    }
}
