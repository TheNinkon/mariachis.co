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
        'slug',
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
            'subscription_listing_limit' => 'integer',
            'subscription_active' => 'boolean',
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

    public function listingLimit(): int
    {
        $this->loadMissing('activeSubscription.plan.entitlements');

        $plan = $this->activeSubscription?->plan;
        $subscriptionPlanLimit = 0;

        if ($plan) {
            $subscriptionPlanLimit = (int) ($plan->entitlementValue(EntitlementKey::MAX_LISTINGS_TOTAL) ?? $plan->listing_limit ?? 0);
        }

        if ($subscriptionPlanLimit > 0) {
            return $subscriptionPlanLimit;
        }

        $explicitLimit = (int) ($this->subscription_listing_limit ?? 0);
        if ($explicitLimit > 0) {
            return $explicitLimit;
        }

        return match ((string) $this->subscription_plan_code) {
            'premium' => 6,
            'pro', 'plus' => 3,
            default => 1,
        };
    }

    public function canCreateMoreListings(): bool
    {
        return $this->listings()->count() < $this->listingLimit();
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
        $base = Str::slug((string) ($this->business_name ?: $this->user?->display_name ?: 'mariachi'));
        if ($base === '') {
            $base = 'mariachi';
        }

        $candidate = $base;
        $counter = 2;

        while (
            self::query()
                ->where('slug', $candidate)
                ->where('id', '!=', $this->id)
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        if ($this->slug !== $candidate) {
            $this->forceFill(['slug' => $candidate])->saveQuietly();
        }
    }
}
