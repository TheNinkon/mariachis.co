<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MariachiProfileHandleAlias extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'mariachi_profile_id',
        'old_slug',
    ];

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }
}
