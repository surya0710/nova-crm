<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdsMonthlyCalculation extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'tax_financial_year_id',
        'payroll_period_id',
        'payroll_run_id',
        'tax_projection_id',
        'month',
        'year',
        'regime',
        'gross_salary',
        'taxable_income_annual',
        'annual_tax_liability',
        'tds_ytd',
        'tds_amount',
        'cess_amount',
        'surcharge_amount',
        'rebate_amount',
        'breakdown',
        'status',
        'calculated_at',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'gross_salary' => 'decimal:2',
            'taxable_income_annual' => 'decimal:2',
            'annual_tax_liability' => 'decimal:2',
            'tds_ytd' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'cess_amount' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'rebate_amount' => 'decimal:2',
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

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function projection(): BelongsTo
    {
        return $this->belongsTo(TaxProjection::class, 'tax_projection_id');
    }
}
