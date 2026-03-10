<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_city_id',
        'name',
        'slug',
        'is_active',
        'sort_order',
        'show_in_search',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_city_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'show_in_search' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCity::class, 'marketplace_city_id');
    }

    public function serviceAreas(): HasMany
    {
        return $this->hasMany(MariachiListingServiceArea::class, 'marketplace_zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearchVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_in_search', true);
    }
}
