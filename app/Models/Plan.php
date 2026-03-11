<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'slug',
        'name',
        'price_cop',
        'billing_cycle',
        'description',
        'badge_text',
        'is_public',
        'listing_limit',
        'included_cities',
        'max_photos_per_listing',
        'max_videos_per_listing',
        'show_whatsapp',
        'show_phone',
        'priority_level',
        'allows_verification',
        'allows_featured_city',
        'allows_featured_home',
        'has_premium_badge',
        'has_advanced_stats',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cop' => 'integer',
            'is_public' => 'boolean',
            'listing_limit' => 'integer',
            'included_cities' => 'integer',
            'max_photos_per_listing' => 'integer',
            'max_videos_per_listing' => 'integer',
            'priority_level' => 'integer',
            'show_whatsapp' => 'boolean',
            'show_phone' => 'boolean',
            'allows_verification' => 'boolean',
            'allows_featured_city' => 'boolean',
            'allows_featured_home' => 'boolean',
            'has_premium_badge' => 'boolean',
            'has_advanced_stats' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class)->orderBy('key');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function entitlementValue(string $key, mixed $default = null): mixed
    {
        if (! $this->relationLoaded('entitlements')) {
            $this->load('entitlements');
        }

        return $this->entitlements->firstWhere('key', $key)?->value ?? $default;
    }
}
