<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogSuggestion extends Model
{
    use HasFactory;

    public const TYPE_EVENT = 'event_type';
    public const TYPE_SERVICE = 'service_type';
    public const TYPE_CITY = 'marketplace_city';
    public const TYPE_ZONE = 'marketplace_zone';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'catalog_type',
        'proposed_name',
        'proposed_slug',
        'context_data',
        'status',
        'admin_notes',
        'submitted_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'context_data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
