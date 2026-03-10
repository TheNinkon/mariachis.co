<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MariachiReview extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_HIDDEN = 'hidden';

    public const VERIFICATION_BASIC = 'basic';
    public const VERIFICATION_MANUAL = 'manual_validated';
    public const VERIFICATION_WITH_EVIDENCE = 'evidence_attached';

    public const MODERATION_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_REPORTED,
        self::STATUS_HIDDEN,
    ];

    public const VERIFICATION_STATUSES = [
        self::VERIFICATION_BASIC,
        self::VERIFICATION_MANUAL,
        self::VERIFICATION_WITH_EVIDENCE,
    ];

    protected $fillable = [
        'quote_conversation_id',
        'client_user_id',
        'mariachi_profile_id',
        'mariachi_listing_id',
        'rating',
        'title',
        'comment',
        'event_date',
        'event_type',
        'moderation_status',
        'verification_status',
        'is_visible',
        'is_spam',
        'spam_score',
        'has_offensive_language',
        'reports_count',
        'latest_report_reason',
        'reported_at',
        'reported_by_user_id',
        'rejection_reason',
        'moderated_by_user_id',
        'moderated_at',
        'mariachi_reply',
        'mariachi_replied_at',
        'mariachi_reply_visible',
        'mariachi_reply_moderation_note',
        'mariachi_reply_moderated_by_user_id',
        'mariachi_reply_moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'event_date' => 'date',
            'is_visible' => 'boolean',
            'is_spam' => 'boolean',
            'spam_score' => 'integer',
            'has_offensive_language' => 'boolean',
            'reports_count' => 'integer',
            'reported_at' => 'datetime',
            'moderated_at' => 'datetime',
            'mariachi_replied_at' => 'datetime',
            'mariachi_reply_visible' => 'boolean',
            'mariachi_reply_moderated_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(QuoteConversation::class, 'quote_conversation_id');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function mariachiListing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MariachiReviewPhoto::class)->orderBy('sort_order');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function mariachiReplyModeratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mariachi_reply_moderated_by_user_id');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query
            ->where('moderation_status', self::STATUS_APPROVED)
            ->where('is_visible', true);
    }

    public function scopeForMariachiUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $builder) use ($userId): void {
            $builder->whereHas('mariachiProfile', function (Builder $profileBuilder) use ($userId): void {
                $profileBuilder->where('user_id', $userId);
            })->orWhereHas('mariachiListing.mariachiProfile', function (Builder $listingProfileBuilder) use ($userId): void {
                $listingProfileBuilder->where('user_id', $userId);
            });
        });
    }

    public function getVerificationLabelAttribute(): string
    {
        return match ($this->verification_status) {
            self::VERIFICATION_MANUAL => 'Validada manualmente',
            self::VERIFICATION_WITH_EVIDENCE => 'Con foto/prueba',
            default => 'Opinion basica',
        };
    }
}
