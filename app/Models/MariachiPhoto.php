<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'mariachi_profile_id',
        'path',
        'title',
        'sort_order',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class, 'mariachi_profile_id');
    }
}
