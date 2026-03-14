<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingPayment extends Model
{
    use HasFactory;

    public const METHOD_WOMPI = 'wompi';
    public const METHOD_NEQUI = 'nequi';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'mariachi_listing_id',
        'mariachi_profile_id',
        'plan_code',
        'duration_months',
        'amount_cop',
        'method',
        'checkout_reference',
        'provider_transaction_id',
        'provider_transaction_status',
        'provider_payload',
        'proof_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'reference_text',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'amount_cop' => 'integer',
            'provider_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MariachiListing::class, 'mariachi_listing_id');
    }

    public function mariachiProfile(): BelongsTo
    {
        return $this->belongsTo(MariachiProfile::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            default => 'Pendiente',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
