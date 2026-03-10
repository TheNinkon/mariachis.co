<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(BlogZone::class)->orderBy('name');
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_city_blog_post')->orderByDesc('published_at');
    }
}
