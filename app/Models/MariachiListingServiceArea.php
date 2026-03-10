<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiListingServiceArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'mariachi_listing_id',
        'marketplace_zone_id',
        'city_name',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_zone_id' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class, 'mariachi_listing_id');
    }

    public function marketplaceZone(): BelongsTo
    {
        return $this->belongsTo(MarketplaceZone::class, 'marketplace_zone_id');
    }
}
