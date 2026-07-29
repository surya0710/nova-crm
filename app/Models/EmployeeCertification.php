<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCertification extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'name',
        'issuing_organization',
        'credential_number',
        'issue_date',
        'expiry_date',
        'credential_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function resolveDisplayStatus(?Carbon $asOf = null): string
    {
        $asOf ??= now();

        if ($this->expiry_date === null) {
            return 'active';
        }

        if ($this->expiry_date->lt($asOf->copy()->startOfDay())) {
            return 'expired';
        }

        $soonDays = (int) config('hrms.certification_expiring_soon_days', 60);
        if ($this->expiry_date->lte($asOf->copy()->addDays($soonDays))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->resolveDisplayStatus();
    }

    public function getDisplayStatusLabelAttribute(): string
    {
        return config('hrms.certification_display_statuses.'.$this->display_status, ucfirst(str_replace('_', ' ', $this->display_status)));
    }
}
