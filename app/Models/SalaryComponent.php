<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SalaryComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    /** @use HasFactory<SalaryComponentFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'component_type',
        'is_taxable',
        'is_recurring',
        'formula_supported',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_recurring' => 'boolean',
            'formula_supported' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function structureComponents(): HasMany
    {
        return $this->hasMany(SalaryStructureComponent::class);
    }
}
