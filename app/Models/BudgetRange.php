<?php

namespace App\Models;

use App\Models\Concerns\HasHomeEditorialVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class BudgetRange extends Model
{
    use HasFactory;
    use HasHomeEditorialVisibility;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_featured',
        'is_active',
        'is_visible_in_home',
        'home_priority',
        'seasonal_start_at',
        'seasonal_end_at',
        'min_active_listings_required',
        'home_clicks_count',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_visible_in_home' => 'boolean',
            'home_priority' => 'integer',
            'seasonal_start_at' => 'datetime',
            'seasonal_end_at' => 'datetime',
            'min_active_listings_required' => 'integer',
            'home_clicks_count' => 'integer',
        ];
    }

    public function mariachiProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MariachiProfile::class);
    }

    public function mariachiListings(): BelongsToMany
    {
        return $this->belongsToMany(MariachiListing::class, 'budget_range_mariachi_listing');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('name');
    }
}
