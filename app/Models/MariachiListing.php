<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MariachiListing extends Model
{
    use HasFactory;

    public const OPEN_DRAFT_LIMIT = 5;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_AWAITING_PLAN = 'awaiting_plan';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';

    public const PAYMENT_NONE = 'none';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_APPROVED = 'approved';
    public const PAYMENT_REJECTED = 'rejected';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_NONE,
        self::PAYMENT_PENDING,
        self::PAYMENT_APPROVED,
        self::PAYMENT_REJECTED,
    ];

    public const REVIEW_DRAFT = 'draft';
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REJECTED = 'rejected';

    public const REVIEW_STATUSES = [
        self::REVIEW_DRAFT,
        self::REVIEW_PENDING,
        self::REVIEW_APPROVED,
        self::REVIEW_REJECTED,
    ];

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
        'review_status',
        'payment_status',
        'is_active',
        'selected_plan_code',
        'plan_duration_months',
        'plan_selected_at',
        'submitted_for_review_at',
        'reviewed_at',
        'reviewed_by_user_id',
        'rejection_reason',
        'activated_at',
        'plan_expires_at',
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
            'plan_duration_months' => 'integer',
            'reviewed_by_user_id' => 'integer',
            'watermark_enabled' => 'boolean',
            'image_hashing_enabled' => 'boolean',
            'has_duplicate_images' => 'boolean',
            'plan_selected_at' => 'datetime',
            'submitted_for_review_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'activated_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $listing): void {
            if (! filled($listing->slug)) {
                $listing->slug = $listing->buildUniqueSlug();
            }

            if (! filled($listing->review_status)) {
                $listing->review_status = self::REVIEW_DRAFT;
            }
        });
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
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

    public function payments(): HasMany
    {
        return $this->hasMany(ListingPayment::class)->latest();
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(ListingPayment::class)->latestOfMany();
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
            ->where('review_status', self::REVIEW_APPROVED)
            ->whereHas('mariachiProfile.user', function (Builder $builder): void {
                $builder->where('role', User::ROLE_MARIACHI)
                    ->where('status', User::STATUS_ACTIVE);
            });
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_PENDING);
    }

    public function scopeOpenDrafts(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where(function (Builder $draftQuery): void {
                    $draftQuery
                        ->where('status', self::STATUS_DRAFT)
                        ->where('review_status', '!=', self::REVIEW_PENDING);
                })
                ->orWhere(function (Builder $paymentQuery): void {
                    $paymentQuery
                        ->where('status', self::STATUS_AWAITING_PAYMENT)
                        ->whereIn('payment_status', [self::PAYMENT_NONE, self::PAYMENT_REJECTED]);
                })
                ->orWhere(function (Builder $rejectedQuery): void {
                    $rejectedQuery
                        ->where('review_status', self::REVIEW_REJECTED)
                        ->whereNotIn('status', [self::STATUS_ACTIVE, self::STATUS_PAUSED]);
                });
        });
    }

    public function scopeApprovedForMarketplace(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_APPROVED);
    }

    public function isOpenDraft(): bool
    {
        if ($this->review_status === self::REVIEW_REJECTED && ! in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PAUSED], true)) {
            return true;
        }

        if ($this->status === self::STATUS_DRAFT) {
            return $this->review_status !== self::REVIEW_PENDING;
        }

        return $this->status === self::STATUS_AWAITING_PAYMENT
            && in_array($this->payment_status, [self::PAYMENT_NONE, self::PAYMENT_REJECTED], true);
    }

    public function isPendingReview(): bool
    {
        return $this->review_status === self::REVIEW_PENDING;
    }

    public function isApprovedForMarketplace(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED
            && $this->status === self::STATUS_ACTIVE
            && $this->is_active;
    }

    public function canOwnerPause(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED
            && $this->status === self::STATUS_ACTIVE
            && $this->is_active
            && $this->hasEffectivePlan();
    }

    public function canOwnerResume(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED
            && $this->status === self::STATUS_PAUSED
            && ! $this->is_active
            && $this->hasEffectivePlan();
    }

    public function canBeSubmittedForReview(): bool
    {
        return in_array($this->review_status, [self::REVIEW_DRAFT, self::REVIEW_REJECTED], true)
            && $this->listing_completed
            && $this->hasApprovedSelectedPlan();
    }

    public function hasEffectivePlan(): bool
    {
        return filled($this->effectivePlanCode());
    }

    public function effectivePlanCode(): ?string
    {
        return $this->effectivePlan()?->code;
    }

    public function effectivePlan(): ?Plan
    {
        if ($this->hasApprovedSelectedPlan()) {
            return Plan::query()
                ->with('entitlements')
                ->where('code', (string) $this->selected_plan_code)
                ->first();
        }

        if (! $this->relationLoaded('mariachiProfile')) {
            $this->load('mariachiProfile.activeSubscription.plan.entitlements');
        } elseif ($this->mariachiProfile && ! $this->mariachiProfile->relationLoaded('activeSubscription')) {
            $this->mariachiProfile->load('activeSubscription.plan.entitlements');
        } elseif ($this->mariachiProfile?->activeSubscription && ! $this->mariachiProfile->activeSubscription->relationLoaded('plan')) {
            $this->mariachiProfile->activeSubscription->load('plan.entitlements');
        } elseif ($this->mariachiProfile?->activeSubscription?->plan && ! $this->mariachiProfile->activeSubscription->plan->relationLoaded('entitlements')) {
            $this->mariachiProfile->activeSubscription->plan->load('entitlements');
        }

        return $this->mariachiProfile?->activeSubscription?->plan;
    }

    public function hasApprovedSelectedPlan(): bool
    {
        return filled($this->selected_plan_code) && $this->payment_status === self::PAYMENT_APPROVED;
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function isPaymentRejected(): bool
    {
        return $this->payment_status === self::PAYMENT_REJECTED;
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PENDING => 'Pago en revision',
            self::PAYMENT_APPROVED => 'Pago aprobado',
            self::PAYMENT_REJECTED => 'Pago rechazado',
            default => 'Sin pago',
        };
    }

    public function hasPremiumMarketplaceBadge(): bool
    {
        $plan = $this->effectivePlan();

        if (! $plan) {
            return false;
        }

        return (bool) $plan->entitlementValue('has_premium_badge', $plan->has_premium_badge);
    }

    public function marketplaceBadgeLabel(): ?string
    {
        $plan = $this->effectivePlan();

        if (! $plan || ! $this->hasPremiumMarketplaceBadge()) {
            return null;
        }

        return filled($plan->badge_text) ? (string) $plan->badge_text : 'VIP';
    }

    public function reviewStatusLabel(): string
    {
        return match ($this->review_status) {
            self::REVIEW_PENDING => 'En revision',
            self::REVIEW_APPROVED => 'Aprobado',
            self::REVIEW_REJECTED => 'Rechazado',
            default => 'Borrador',
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{question:string,answer:string,is_system:bool,sort_order:int,is_visible:bool}>
     */
    public function systemFaqRows(): Collection
    {
        $this->loadMissing([
            'eventTypes:id,name',
            'serviceTypes:id,name',
            'groupSizeOptions:id,name,sort_order',
        ]);

        $groupSizeSummary = $this->sentenceFromNames($this->groupSizeOptions->pluck('name'));
        $eventSummary = $this->sentenceFromNames($this->eventTypes->pluck('name'));
        $serviceSummary = $this->sentenceFromNames($this->serviceTypes->pluck('name'));
        $priceLabel = $this->base_price !== null
            ? '$'.number_format((float) $this->base_price, 0, ',', '.').' COP'
            : null;

        $rows = [
            [
                'question' => '¿Cuántos integrantes son?',
                'answer' => $groupSizeSummary !== ''
                    ? 'Este anuncio opera en formatos como '.$groupSizeSummary.'. Si necesitas un formato puntual, puedes pedirlo en la cotización.'
                    : 'La cantidad de integrantes se ajusta según el show solicitado. Usa la cotización para pedir una propuesta acorde a tu evento.',
            ],
            [
                'question' => '¿Qué tipo de eventos atienden?',
                'answer' => $eventSummary !== ''
                    ? 'Atienden eventos como '.$eventSummary.'.'
                    : ($serviceSummary !== ''
                        ? 'Ofrecen formatos como '.$serviceSummary.' y otros shows bajo solicitud.'
                        : 'Atienden serenatas, celebraciones privadas y eventos corporativos según disponibilidad.'),
            ],
            [
                'question' => '¿Cómo puedo solicitar más información?',
                'answer' => $priceLabel
                    ? 'Puedes dejar tu solicitud desde el formulario del anuncio. El precio base actual inicia desde '.$priceLabel.' y luego se ajusta según fecha, ciudad y formato.'
                    : 'Puedes dejar tu solicitud desde el formulario del anuncio y el mariachi responderá con una propuesta según fecha, ciudad y formato.',
            ],
        ];

        return collect($rows)->values()->map(
            fn (array $row, int $index): array => [
                'question' => $row['question'],
                'answer' => $row['answer'],
                'is_system' => true,
                'sort_order' => $index + 1,
                'is_visible' => true,
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{question:string,answer:string,is_system:bool,sort_order:int,is_visible:bool}>
     */
    public function renderedFaqRows(bool $includeHiddenUserFaqs = false): Collection
    {
        $userFaqs = $this->relationLoaded('faqs')
            ? $this->faqs
            : $this->faqs()->orderBy('sort_order')->get();

        if (! $includeHiddenUserFaqs) {
            $userFaqs = $userFaqs->where('is_visible', true)->values();
        }

        return $this->systemFaqRows()->concat(
            $userFaqs->values()->map(
                fn (MariachiListingFaq $faq, int $index): array => [
                    'question' => trim((string) $faq->question),
                    'answer' => trim((string) $faq->answer),
                    'is_system' => false,
                    'sort_order' => max(4, (int) $faq->sort_order ?: $index + 4),
                    'is_visible' => (bool) $faq->is_visible,
                ]
            )->filter(fn (array $faq): bool => $faq['question'] !== '' && $faq['answer'] !== '')
        )->values();
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

    /**
     * @param  \Illuminate\Support\Collection<int, string>|array<int, string>  $names
     */
    private function sentenceFromNames(Collection|array $names): string
    {
        $values = collect($names)
            ->map(fn (mixed $name): string => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return (string) $values->first();
        }

        if ($count === 2) {
            return $values->implode(' y ');
        }

        $head = $values->slice(0, -1)->implode(', ');
        $tail = $values->last();

        return $head.' y '.$tail;
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
