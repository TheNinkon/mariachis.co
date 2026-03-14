<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profile_verification_plans')) {
            Schema::create('profile_verification_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->unsignedTinyInteger('duration_months');
                $table->unsignedInteger('amount_cop');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mariachi_profile_handle_aliases')) {
            Schema::create('mariachi_profile_handle_aliases', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
                $table->string('old_slug')->unique();
                $table->timestamps();

                $table->index('mariachi_profile_id', 'profile_handle_aliases_profile_index');
            });
        }

        DB::table('profile_verification_plans')->upsert([
            [
                'code' => 'verification-1m',
                'name' => '1 mes',
                'duration_months' => 1,
                'amount_cop' => 18900,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'verification-3m',
                'name' => '3 meses',
                'duration_months' => 3,
                'amount_cop' => 56700,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'verification-12m',
                'name' => '12 meses',
                'duration_months' => 12,
                'amount_cop' => 226800,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code'], ['name', 'duration_months', 'amount_cop', 'is_active', 'sort_order', 'updated_at']);

        $reservedHandles = collect(config('seo.reserved_slugs', []))
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
            ->map(static fn (mixed $value): string => Str::slug((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $profiles = DB::table('mariachi_profiles')
            ->select('id', 'slug', 'slug_locked', 'verification_status', 'verification_expires_at')
            ->orderBy('id')
            ->get();

        foreach ($profiles as $profile) {
            $slugLocked = (bool) ($profile->slug_locked ?? false);
            $verificationStatus = (string) ($profile->verification_status ?? 'unverified');
            $expiresAt = $profile->verification_expires_at ? Carbon::parse((string) $profile->verification_expires_at) : null;
            $hasActiveVerification = $verificationStatus === 'verified' && (! $expiresAt || $expiresAt->isFuture());

            if ($slugLocked || $hasActiveVerification) {
                continue;
            }

            $currentSlug = trim((string) ($profile->slug ?? ''));
            if ($currentSlug === '' || str_starts_with($currentSlug, 'm-')) {
                if ($currentSlug === '') {
                    DB::table('mariachi_profiles')
                        ->where('id', $profile->id)
                        ->update(['slug' => $this->randomHandle($reservedHandles)]);
                }

                continue;
            }

            $newSlug = $this->randomHandle($reservedHandles);

            DB::table('mariachi_profile_handle_aliases')->updateOrInsert(
                ['old_slug' => $currentSlug],
                [
                    'mariachi_profile_id' => $profile->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('mariachi_profiles')
                ->where('id', $profile->id)
                ->update(['slug' => $newSlug]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_profile_handle_aliases');
        Schema::dropIfExists('profile_verification_plans');
    }

    /**
     * @param  list<string>  $reservedHandles
     */
    private function randomHandle(array $reservedHandles): string
    {
        do {
            $candidate = 'm-'.Str::lower(Str::random(8));
            $inUse = DB::table('mariachi_profiles')->where('slug', $candidate)->exists()
                || DB::table('mariachi_profile_handle_aliases')->where('old_slug', $candidate)->exists()
                || in_array($candidate, $reservedHandles, true);
        } while ($inUse);

        return $candidate;
    }
};
