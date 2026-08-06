<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxFinancialYear extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'label',
        'assessment_year',
        'start_date',
        'end_date',
        'default_regime',
        'is_active',
        'version',
        'configuration',
        'custom_fields',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'version' => 'integer',
            'configuration' => 'array',
            'custom_fields' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function slabs(): HasMany
    {
        return $this->hasMany(TaxSlab::class)->orderBy('sort_order');
    }

    public function regimes(): HasMany
    {
        return $this->hasMany(EmployeeTaxRegime::class);
    }

    public function projections(): HasMany
    {
        return $this->hasMany(TaxProjection::class);
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(TaxDeclaration::class);
    }

    public function isEffectiveOn(\DateTimeInterface|string $date): bool
    {
        $date = \Carbon\Carbon::parse($date)->startOfDay();

        return $date->betweenIncluded($this->start_date->startOfDay(), $this->end_date->endOfDay());
    }
}
