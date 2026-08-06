<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxProjection extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'tax_financial_year_id',
        'regime',
        'projected_gross',
        'projected_taxable',
        'projected_tax',
        'projected_cess',
        'projected_surcharge',
        'projected_rebate',
        'annual_tax_liability',
        'tds_already_deducted',
        'remaining_tds',
        'remaining_months',
        'monthly_tds',
        'breakdown',
        'source',
        'calculated_at',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'projected_gross' => 'decimal:2',
            'projected_taxable' => 'decimal:2',
            'projected_tax' => 'decimal:2',
            'projected_cess' => 'decimal:2',
            'projected_surcharge' => 'decimal:2',
            'projected_rebate' => 'decimal:2',
            'annual_tax_liability' => 'decimal:2',
            'tds_already_deducted' => 'decimal:2',
            'remaining_tds' => 'decimal:2',
            'remaining_months' => 'integer',
            'monthly_tds' => 'decimal:2',
            'breakdown' => 'array',
            'calculated_at' => 'datetime',
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
}
