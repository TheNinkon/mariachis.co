<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuoteConversation extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESPONDED = 'responded';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'client_user_id',
        'mariachi_profile_id',
        'mariachi_listing_id',
        'status',
        'contact_phone',
        'event_date',
        'event_city',
        'event_notes',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'last_message_at' => 'datetime',
        ];
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

    public function messages(): HasMany
    {
        return $this->hasMany(QuoteMessage::class)->oldest('created_at');
    }

    public function review(): HasOne
    {
        return $this->hasOne(MariachiReview::class, 'quote_conversation_id');
    }

    public function scopeForClient(Builder $query, int $userId): Builder
    {
        return $query->where('client_user_id', $userId);
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
}
