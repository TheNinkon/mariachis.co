<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'author_id',
        'event_type_id',
        'title',
        'slug',
        'meta_title',
        'featured_image',
        'og_image',
        'robots',
        'canonical_override',
        'excerpt',
        'meta_description',
        'content',
        'status',
        'city_name',
        'zone_name',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function eventTypes(): BelongsToMany
    {
        return $this->belongsToMany(EventType::class, 'blog_post_event_type')
            ->orderBy('name');
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(BlogCity::class, 'blog_city_blog_post')
            ->orderBy('name');
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(BlogZone::class, 'blog_post_blog_zone')
            ->orderBy('name');
    }

    public function getPrimaryCityNameAttribute(): ?string
    {
        if ($this->relationLoaded('cities') && $this->cities->isNotEmpty()) {
            return $this->cities->first()?->name;
        }

        $cityName = $this->cities()
            ->select('blog_cities.name')
            ->value('blog_cities.name');

        return $cityName ?: $this->city_name;
    }

    public function getPrimaryZoneNameAttribute(): ?string
    {
        if ($this->relationLoaded('zones') && $this->zones->isNotEmpty()) {
            return $this->zones->first()?->name;
        }

        $zoneName = $this->zones()
            ->select('blog_zones.name')
            ->value('blog_zones.name');

        return $zoneName ?: $this->zone_name;
    }

    public function getPrimaryEventTypeNameAttribute(): ?string
    {
        if ($this->relationLoaded('eventTypes') && $this->eventTypes->isNotEmpty()) {
            return $this->eventTypes->first()?->name;
        }

        $eventName = $this->eventTypes()
            ->select('event_types.name')
            ->value('event_types.name');

        return $eventName ?: $this->eventType?->name;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
