<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiListingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'mariachi_listing_id',
        'path',
        'title',
        'sort_order',
        'is_featured',
        'image_hash',
        'watermark_applied_at',
        'watermark_version',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'watermark_applied_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class, 'mariachi_listing_id');
    }

    public function mediaHash(): BelongsTo
    {
        return $this->belongsTo(MediaHash::class, 'id', 'media_id')
            ->where('media_type', 'listing_photo');
    }
}
