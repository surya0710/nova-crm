<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeTaxRegime extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'tax_financial_year_id',
        'regime',
        'status',
        'effective_from',
        'effective_until',
        'notes',
        'selected_by',
        'selected_at',
        'meta',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'selected_at' => 'datetime',
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

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
