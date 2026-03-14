<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountActivationPlan extends Model
{
    use HasFactory;

    public const BILLING_TYPE_ONE_TIME = 'one_time';

    protected $fillable = [
        'code',
        'name',
        'billing_type',
        'amount_cop',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount_cop' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountActivationPayment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
