<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayslipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Payslip extends Model
{
    /** @use HasFactory<PayslipFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'payroll_result_id',
        'payroll_publication_id',
        'employee_id',
        'payslip_number',
        'gross_salary',
        'total_earnings',
        'total_deductions',
        'employer_contributions',
        'net_salary',
        'snapshot',
        'calculation_hash',
        'pdf_disk',
        'pdf_path',
        'generated_at',
        'emailed_at',
        'email_count',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'employer_contributions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'snapshot' => 'array',
            'generated_at' => 'datetime',
            'emailed_at' => 'datetime',
            'email_count' => 'integer',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function payrollResult(): BelongsTo
    {
        return $this->belongsTo(PayrollResult::class);
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PayrollPublication::class, 'payroll_publication_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasPdf(): bool
    {
        return filled($this->pdf_disk) && filled($this->pdf_path);
    }

    public function pdfExists(): bool
    {
        return $this->hasPdf() && Storage::disk($this->pdf_disk)->exists($this->pdf_path);
    }
}
