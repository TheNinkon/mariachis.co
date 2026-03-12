<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'audience',
        'description',
        'subject',
        'body_html',
        'variables_schema',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'variables_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
