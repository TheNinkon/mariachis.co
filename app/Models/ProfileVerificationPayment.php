<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProfileVerificationPayment extends Model
{
    use HasFactory;

    public const METHOD_NEQUI = 'nequi';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'mariachi_profile_id',
        'plan_code',
        'duration_months',
        'amount_cop',
        'method',
        'proof_path',
        'status',
        'reference_text',
        'reviewed_by_user_id',
        'reviewed_at',
        'starts_at',
        'ends_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'amount_cop' => 'integer',
            'reviewed_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function verificationRequest(): HasOne
    {
        return $this->hasOne(VerificationRequest::class, 'profile_verification_payment_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Pago aprobado',
            self::STATUS_REJECTED => 'Pago rechazado',
            default => 'Pago pendiente',
        };
    }
}
