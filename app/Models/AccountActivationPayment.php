<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountActivationPayment extends Model
{
    use HasFactory;

    public const METHOD_WOMPI = 'wompi';
    public const METHOD_NEQUI = 'nequi';

    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'account_activation_plan_id',
        'amount_cop',
        'method',
        'checkout_reference',
        'provider_transaction_id',
        'provider_transaction_status',
        'provider_payload',
        'proof_path',
        'status',
        'reference_text',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_cop' => 'integer',
            'provider_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AccountActivationPlan::class, 'account_activation_plan_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            default => 'En revision',
        };
    }
}
