<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PayrollBankExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PayrollBankExport extends Model
{
    /** @use HasFactory<PayrollBankExportFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'payroll_run_id',
        'export_number',
        'format',
        'file_disk',
        'file_path',
        'employee_count',
        'total_amount',
        'status',
        'meta',
        'exported_by',
        'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_count' => 'integer',
            'total_amount' => 'decimal:2',
            'meta' => 'array',
            'exported_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }

    public function fileExists(): bool
    {
        return $this->file_disk && $this->file_path
            && Storage::disk($this->file_disk)->exists($this->file_path);
    }
}
