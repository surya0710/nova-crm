<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxProofAudit extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'tax_proof_id',
        'action',
        'from_status',
        'to_status',
        'approved_amount',
        'comments',
        'actor_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'approved_amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function proof(): BelongsTo
    {
        return $this->belongsTo(TaxProof::class, 'tax_proof_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
