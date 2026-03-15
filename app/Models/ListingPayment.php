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

    public const OPERATION_INITIAL = 'initial';
    public const OPERATION_UPGRADE = 'upgrade';
    public const OPERATION_RENEWAL = 'renewal';
    public const OPERATION_RETRY = 'retry';

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
        'operation_type',
        'retry_of_payment_id',
        'source_plan_code',
        'target_plan_code',
        'subtotal_amount_cop',
        'discount_amount_cop',
        'base_amount_cop',
        'applied_credit_cop',
        'final_amount_cop',
        'operation_metadata',
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
            'subtotal_amount_cop' => 'integer',
            'discount_amount_cop' => 'integer',
            'base_amount_cop' => 'integer',
            'applied_credit_cop' => 'integer',
            'final_amount_cop' => 'integer',
            'provider_payload' => 'array',
            'operation_metadata' => 'array',
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

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retry_of_payment_id');
    }

    public function targetPlanCode(): string
    {
        return (string) ($this->target_plan_code ?: $this->plan_code);
    }

    public function chargedAmountCop(): int
    {
        return (int) ($this->final_amount_cop ?: $this->amount_cop);
    }

    public function effectiveOperationType(): string
    {
        if ($this->operation_type !== self::OPERATION_RETRY) {
            return (string) $this->operation_type;
        }

        return (string) data_get($this->operation_metadata, 'retry_context', self::OPERATION_RETRY);
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

    public function operationLabel(): string
    {
        $label = match ($this->effectiveOperationType()) {
            self::OPERATION_UPGRADE => 'Upgrade',
            self::OPERATION_RENEWAL => 'Renovacion',
            default => 'Compra inicial',
        };

        if ($this->operation_type === self::OPERATION_RETRY) {
            return 'Reintento · '.$label;
        }

        return $label;
    }
}
