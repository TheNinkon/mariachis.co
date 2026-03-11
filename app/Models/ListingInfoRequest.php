<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingInfoRequest extends Model
{
    public const STATUS_NEW = 'new';

    protected $fillable = [
        'mariachi_profile_id',
        'mariachi_listing_id',
        'client_user_id',
        'status',
        'name',
        'email',
        'phone',
        'event_date',
        'event_city',
        'message',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
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

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }
}
