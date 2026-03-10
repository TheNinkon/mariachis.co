<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function mariachiProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MariachiProfile::class);
    }

    public function mariachiListings(): BelongsToMany
    {
        return $this->belongsToMany(MariachiListing::class, 'event_type_mariachi_listing');
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_event_type');
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
