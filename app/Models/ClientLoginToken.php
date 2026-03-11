<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLoginToken extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'expires_at',
        'used_at',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasBeenUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function markAsUsed(): void
    {
        $this->forceFill([
            'used_at' => now(),
        ])->save();
    }
}
