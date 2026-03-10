<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_city_id',
        'name',
        'slug',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(BlogCity::class, 'blog_city_id');
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_blog_zone')->orderByDesc('published_at');
    }
}
