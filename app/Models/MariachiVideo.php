<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'mariachi_profile_id',
        'url',
        'platform',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class, 'mariachi_profile_id');
    }
}
