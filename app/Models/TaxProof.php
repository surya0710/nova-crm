<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxProof extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'tax_declaration_id',
        'tax_declaration_item_id',
        'employee_id',
        'proof_number',
        'category',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'claimed_amount',
        'approved_amount',
        'status',
        'comments',
        'uploaded_by',
        'verified_by',
        'verified_at',
        'meta',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'file_size' => 'integer',
            'verified_at' => 'datetime',
            'meta' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(TaxDeclaration::class, 'tax_declaration_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TaxDeclarationItem::class, 'tax_declaration_item_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TaxProofAudit::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
