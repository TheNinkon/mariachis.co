<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiProfileStat extends Model
{
    protected $fillable = [
        'mariachi_profile_id',
        'total_views',
        'total_favorites',
        'total_quotes',
    ];

    protected function casts(): array
    {
        return [
            'total_views' => 'integer',
            'total_favorites' => 'integer',
            'total_quotes' => 'integer',
        ];
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }
}
