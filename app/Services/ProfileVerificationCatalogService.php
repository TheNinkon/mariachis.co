<?php

namespace App\Services;

use App\Models\ProfileVerificationPlan;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProfileVerificationCatalogService
{
    /**
     * @return array<string, array{
     *   code:string,
     *   name:string,
     *   duration_months:int,
     *   amount_cop:int,
     *   description:string,
     *   is_active:bool,
     *   sort_order:int
     * }>
     */
    public function plans(): array
    {
        return ProfileVerificationPlan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('duration_months')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (ProfileVerificationPlan $plan): array {
                $duration = max(1, (int) $plan->duration_months);

                return [$plan->code => [
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'duration_months' => $duration,
                    'amount_cop' => (int) $plan->amount_cop,
                    'description' => $this->descriptionFor($plan),
                    'is_active' => (bool) $plan->is_active,
                    'sort_order' => (int) $plan->sort_order,
                ]];
            })
            ->all();
    }

    /**
     * @return array{
     *   code:string,
     *   name:string,
     *   duration_months:int,
     *   amount_cop:int,
     *   description:string,
     *   is_active:bool,
     *   sort_order:int
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

    public function baseAmount(): int
    {
        $plans = collect($this->plans());

        return (int) ($plans->min('amount_cop') ?? 0);
    }

    private function descriptionFor(ProfileVerificationPlan $plan): string
    {
        $duration = max(1, (int) $plan->duration_months);

        return match ($duration) {
            1 => 'Verificacion premium por 1 mes para habilitar insignia, handle personalizado y foto de perfil.',
            3 => 'Extiende la verificacion premium durante 3 meses continuos con handle y foto de perfil activos.',
            12 => 'Manten la insignia, el handle premium y la foto de perfil durante 12 meses.',
            default => 'Activa la verificacion premium durante '.$duration.' meses con insignia, handle personalizado y foto de perfil.',
        };
    }
}
