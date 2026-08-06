<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxDeclaration extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'tax_financial_year_id',
        'declaration_number',
        'status',
        'declared_total',
        'approved_total',
        'submitted_at',
        'verified_at',
        'submitted_by',
        'verified_by',
        'rejection_reason',
        'verifier_comments',
        'meta',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'declared_total' => 'decimal:2',
            'approved_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'meta' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(TaxFinancialYear::class, 'tax_financial_year_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaxDeclarationItem::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(TaxProof::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_DRAFT || $this->status === self::STATUS_REJECTED;
    }

    public function canVerify(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }
}
