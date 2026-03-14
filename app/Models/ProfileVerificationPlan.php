<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileVerificationPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'duration_months',
        'amount_cop',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'amount_cop' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
