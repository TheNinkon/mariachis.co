<?php

namespace App\Models;

use App\Support\Entitlements\EntitlementKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MariachiProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_name',
        'country',
        'state',
        'postal_code',
        'address',
        'latitude',
        'longitude',
        'whatsapp',
        'business_name',
        'logo_path',
        'cover_path',
        'slug',
        'slug_locked',
        'responsible_name',
        'short_description',
        'full_description',
        'base_price',
        'website',
        'instagram',
        'facebook',
        'tiktok',
        'youtube',
        'travels_to_other_cities',
        'profile_completion',
        'profile_completed',
        'stage_status',
        'verification_status',
        'verification_notes',
        'verification_expires_at',
        'notification_preferences',
        'subscription_plan_code',
        'subscription_listing_limit',
        'subscription_active',
        'default_mariachi_listing_id',
    ];

    protected function casts(): array
    {
        return [
            'profile_completed' => 'boolean',
            'profile_completion' => 'integer',
            'base_price' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'travels_to_other_cities' => 'boolean',
            'slug_locked' => 'boolean',
            'subscription_listing_limit' => 'integer',
            'subscription_active' => 'boolean',
            'verification_expires_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MariachiPhoto::class)->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MariachiListing::class)->latest('updated_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('starts_at');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->active()
            ->latestOfMany('id');
    }

    public function activeListings(): HasMany
    {
        return $this->hasMany(MariachiListing::class)
            ->where('status', MariachiListing::STATUS_ACTIVE)
            ->where('is_active', true)
            ->where('review_status', MariachiListing::REVIEW_APPROVED);
    }

    public function defaultListing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class, 'default_mariachi_listing_id');
    }

    public function entitlementOverrides(): HasMany
    {
        return $this->hasMany(MariachiEntitlementOverride::class)->orderBy('key');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(MariachiVideo::class)->latest();
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(MariachiServiceArea::class)->latest();
    }

    public function eventTypes(): BelongsToMany
    {
        $relation = $this->belongsToMany(EventType::class);
        if (Schema::hasColumn('event_types', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function serviceTypes(): BelongsToMany
    {
        $relation = $this->belongsToMany(ServiceType::class);
        if (Schema::hasColumn('service_types', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function groupSizeOptions(): BelongsToMany
    {
        return $this->belongsToMany(GroupSizeOption::class)->orderBy('sort_order');
    }

    public function budgetRanges(): BelongsToMany
    {
        $relation = $this->belongsToMany(BudgetRange::class);
        if (Schema::hasColumn('budget_ranges', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_favorites')
            ->withTimestamps();
    }

    public function quoteConversations(): HasMany
    {
        return $this->hasMany(QuoteConversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MariachiReview::class);
    }

    public function stat(): HasOne
    {
        return $this->hasOne(MariachiProfileStat::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class)->latest('submitted_at');
    }

    public function verificationPayments(): HasMany
    {
        return $this->hasMany(ProfileVerificationPayment::class)->latest('created_at');
    }

    public function latestVerificationPayment(): HasOne
    {
        return $this->hasOne(ProfileVerificationPayment::class)->latestOfMany();
    }

    public function handleAliases(): HasMany
    {
        return $this->hasMany(MariachiProfileHandleAlias::class)->latest('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $builder): void {
                $builder->where('profile_completed', true)
                    ->orWhereHas('activeListings');
            })
            ->whereHas('user', function (Builder $userQuery): void {
                $userQuery->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE);
            });
    }

    public function scopePublicPageVisible(Builder $query): Builder
    {
        return $query
            ->whereHas('user', function (Builder $userQuery): void {
                $userQuery->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE);
            });
    }

    public function publishedListingLimit(): int
    {
        $this->loadMissing('activeSubscription.plan.entitlements');

        $plan = $this->activeSubscription?->plan;
        $subscriptionPlanLimit = null;

        if ($plan) {
            $subscriptionPlanLimit = $plan->entitlementValue(
                EntitlementKey::MAX_PUBLISHED_LISTINGS,
                $plan->entitlementValue(EntitlementKey::MAX_LISTINGS_TOTAL, $plan->listing_limit)
            );
        }

        if ($subscriptionPlanLimit !== null) {
            return max(0, (int) $subscriptionPlanLimit);
        }

        $explicitLimit = (int) ($this->subscription_listing_limit ?? 0);
        if ($explicitLimit >= 0) {
            return max(0, $explicitLimit);
        }

        return 0;
    }

    public function listingLimit(): int
    {
        return $this->publishedListingLimit();
    }

    public function openDraftLimit(): int
    {
        $this->loadMissing('activeSubscription.plan.entitlements');

        $plan = $this->activeSubscription?->plan;

        if ($plan) {
            return max(0, (int) ($plan->entitlementValue(
                EntitlementKey::MAX_OPEN_DRAFTS,
                EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS)
            ) ?? EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS)));
        }

        return (int) EntitlementKey::defaultFor(EntitlementKey::MAX_OPEN_DRAFTS);
    }

    public function hasUnlimitedPublishedListings(): bool
    {
        return $this->publishedListingLimit() === 0;
    }

    public function canCreateMoreListings(): bool
    {
        $limit = $this->openDraftLimit();

        if ($limit === 0) {
            return true;
        }

        return $this->listings()->openDrafts()->count() < $limit;
    }

    public function canPublishMoreListings(): bool
    {
        $limit = $this->publishedListingLimit();

        if ($limit === 0) {
            return true;
        }

        return $this->activeListings()->count() < $limit;
    }

    public function resolveDefaultListing(): ?MariachiListing
    {
        if ($this->relationLoaded('defaultListing') && $this->defaultListing) {
            return $this->defaultListing;
        }

        return $this->defaultListing
            ?? $this->activeListings()->latest('updated_at')->first()
            ?? $this->listings()->latest('updated_at')->first();
    }

    public function ensureSlug(): void
    {
        if ($this->slug_locked) {
            return;
        }

        if (filled($this->slug)) {
            return;
        }

        $candidate = $this->generateRandomPublicHandle();

        $this->forceFill(['slug' => $candidate])->saveQuietly();
    }

    public function ensureBusinessNameFromUser(): void
    {
        if (filled($this->business_name)) {
            return;
        }

        $this->loadMissing('user');

        $fallbackName = trim((string) ($this->user?->display_name ?? ''));
        if ($fallbackName === '') {
            return;
        }

        $this->forceFill(['business_name' => $fallbackName])->saveQuietly();
    }

    public function hasActiveVerification(): bool
    {
        if ($this->verification_status !== 'verified') {
            return false;
        }

        return ! $this->verification_expires_at || $this->verification_expires_at->isFuture();
    }

    public function hasApprovedListingForProfilePhoto(): bool
    {
        return $this->listings()
            ->where('payment_status', MariachiListing::PAYMENT_APPROVED)
            ->where('review_status', MariachiListing::REVIEW_APPROVED)
            ->exists();
    }

    public function canManageProfilePhoto(): bool
    {
        return $this->hasActiveVerification() || $this->hasApprovedListingForProfilePhoto();
    }

    public function shouldShowProfilePhoto(): bool
    {
        return $this->canManageProfilePhoto() && filled($this->logo_path);
    }

    public function canManageProfileCover(): bool
    {
        return $this->hasActiveVerification();
    }

    public function shouldShowProfileCover(): bool
    {
        return $this->canManageProfileCover() && filled($this->cover_path);
    }

    public function avatarDisplayName(): string
    {
        return trim((string) ($this->business_name ?: $this->user?->display_name ?: 'Mariachi'));
    }

    public function avatarInitials(): string
    {
        $name = $this->avatarDisplayName();
        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = collect($parts)
            ->filter()
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        $initials = Str::substr($initials, 0, 2);

        return $initials !== '' ? $initials : 'MR';
    }

    private function generateRandomPublicHandle(): string
    {
        do {
            $candidate = 'm-'.Str::lower(Str::random(8));
        } while ($this->slugExists($candidate) || in_array($candidate, $this->reservedHandles(), true));

        return $candidate;
    }

    private function slugExists(string $candidate): bool
    {
        $slugInUse = self::query()
            ->where('slug', $candidate)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($slugInUse) {
            return true;
        }

        if (! Schema::hasTable('mariachi_profile_handle_aliases')) {
            return false;
        }

        return MariachiProfileHandleAlias::query()
            ->where('old_slug', $candidate)
            ->exists();
    }

    /**
     * @return list<string>
     */
    private function reservedHandles(): array
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
}
