<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MariachiListing extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AWAITING_PLAN = 'awaiting_plan';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    protected $fillable = [
        'mariachi_profile_id',
        'slug',
        'title',
        'short_description',
        'description',
        'base_price',
        'country',
        'state',
        'city_name',
        'zone_name',
        'marketplace_city_id',
        'postal_code',
        'address',
        'latitude',
        'longitude',
        'google_place_id',
        'google_location_payload',
        'travels_to_other_cities',
        'listing_completion',
        'listing_completed',
        'status',
        'is_active',
        'selected_plan_code',
        'plan_selected_at',
        'activated_at',
        'deactivated_at',
        'watermark_enabled',
        'image_hashing_enabled',
        'has_duplicate_images',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'google_location_payload' => 'array',
            'travels_to_other_cities' => 'boolean',
            'marketplace_city_id' => 'integer',
            'listing_completion' => 'integer',
            'listing_completed' => 'boolean',
            'is_active' => 'boolean',
            'watermark_enabled' => 'boolean',
            'image_hashing_enabled' => 'boolean',
            'has_duplicate_images' => 'boolean',
            'plan_selected_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $listing): void {
            if (! filled($listing->slug)) {
                $listing->slug = $listing->buildUniqueSlug();
            }
        });
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function marketplaceCity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCity::class, 'marketplace_city_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MariachiListingPhoto::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(MariachiListingVideo::class)->latest();
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(MariachiListingServiceArea::class)->latest();
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(MariachiListingFaq::class)->orderBy('sort_order');
    }

    public function eventTypes(): BelongsToMany
    {
        $relation = $this->belongsToMany(EventType::class, 'event_type_mariachi_listing');
        if (Schema::hasColumn('event_types', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function serviceTypes(): BelongsToMany
    {
        $relation = $this->belongsToMany(ServiceType::class, 'mariachi_listing_service_type');
        if (Schema::hasColumn('service_types', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function groupSizeOptions(): BelongsToMany
    {
        return $this->belongsToMany(GroupSizeOption::class, 'group_size_option_mariachi_listing')
            ->orderBy('sort_order');
    }

    public function budgetRanges(): BelongsToMany
    {
        $relation = $this->belongsToMany(BudgetRange::class, 'budget_range_mariachi_listing');
        if (Schema::hasColumn('budget_ranges', 'sort_order')) {
            $relation->orderBy('sort_order');
        }

        return $relation->orderBy('name');
    }

    public function quoteConversations(): HasMany
    {
        return $this->hasMany(QuoteConversation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MariachiReview::class);
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_favorites', 'mariachi_listing_id', 'user_id')
            ->withTimestamps();
    }

    public function recentViews(): HasMany
    {
        return $this->hasMany(ClientRecentView::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(AdPromotion::class, 'mariachi_listing_id')->latest('starts_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereHas('mariachiProfile.user', function (Builder $builder): void {
                $builder->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE);
            });
    }

    public function getBusinessNameAttribute(): ?string
    {
        return $this->mariachiProfile?->business_name;
    }

    public function getResponsibleNameAttribute(): ?string
    {
        return $this->mariachiProfile?->responsible_name;
    }

    public function getProfileCompletionAttribute(): int
    {
        return (int) $this->listing_completion;
    }

    public function getFullDescriptionAttribute(): ?string
    {
        return $this->description;
    }

    public function getWhatsappAttribute(): ?string
    {
        return $this->mariachiProfile?->whatsapp;
    }

    public function getWebsiteAttribute(): ?string
    {
        return $this->mariachiProfile?->website;
    }

    public function getInstagramAttribute(): ?string
    {
        return $this->mariachiProfile?->instagram;
    }

    public function getFacebookAttribute(): ?string
    {
        return $this->mariachiProfile?->facebook;
    }

    public function getTiktokAttribute(): ?string
    {
        return $this->mariachiProfile?->tiktok;
    }

    public function getYoutubeAttribute(): ?string
    {
        return $this->mariachiProfile?->youtube;
    }

    public function getUserAttribute(): ?User
    {
        return $this->mariachiProfile?->user;
    }

    public function ensureSlug(): void
    {
        $targetSlug = $this->buildUniqueSlug();

        if ($this->slug !== $targetSlug) {
            $this->forceFill(['slug' => $targetSlug])->saveQuietly();
        }
    }

    private function buildUniqueSlug(): string
    {
        $base = Str::slug((string) ($this->slug ?: $this->title ?: $this->business_name ?: 'anuncio-mariachi'));
        if ($base === '') {
            $base = 'anuncio-mariachi';
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

        return $candidate;
    }
}
