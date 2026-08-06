<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form16Record extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'tax_financial_year_id',
        'form_number',
        'status',
        'part_a',
        'part_b',
        'employer_details',
        'employee_details',
        'salary_breakup',
        'deductions',
        'tax_paid',
        'generated_by',
        'generated_at',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'part_a' => 'array',
            'part_b' => 'array',
            'employer_details' => 'array',
            'employee_details' => 'array',
            'salary_breakup' => 'array',
            'deductions' => 'array',
            'tax_paid' => 'array',
            'generated_at' => 'datetime',
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

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
