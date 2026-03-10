<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'mariachi_profile_id',
        'mariachi_listing_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function mariachiListing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class);
    }
}
