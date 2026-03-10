<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
        'is_featured',
        'show_in_search',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'show_in_search' => 'boolean',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(MarketplaceZone::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MariachiListing::class, 'marketplace_city_id');
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
