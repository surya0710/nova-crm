<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxDeclarationItem extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'tax_declaration_id',
        'category',
        'section',
        'label',
        'declared_amount',
        'approved_amount',
        'status',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'declared_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(TaxDeclaration::class, 'tax_declaration_id');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(TaxProof::class, 'tax_declaration_item_id');
    }
}
