<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaHash extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_type',
        'media_id',
        'mariachi_profile_id',
        'mariachi_listing_id',
        'file_path',
        'hash_algorithm',
        'hash_value',
        'is_duplicate',
        'duplicate_of_media_hash_id',
        'first_seen_at',
        'last_seen_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_duplicate' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function mariachiListing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_media_hash_id');
    }
}
