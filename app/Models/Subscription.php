<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REPLACED = 'replaced';

    protected $fillable = [
        'mariachi_profile_id',
        'plan_id',
        'status',
        'starts_at',
        'renews_at',
        'ends_at',
        'cancelled_at',
        'base_amount_cop',
        'extra_city_amount_cop',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'renews_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'base_amount_cop' => 'integer',
            'extra_city_amount_cop' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function additionalCities(): HasMany
    {
        return $this->hasMany(SubscriptionCity::class);
    }

    public function adPromotions(): HasMany
    {
        return $this->hasMany(AdPromotion::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $builder): void {
                $builder->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }
}
