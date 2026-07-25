<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EmployeeStatutoryProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeStatutoryProfile extends Model
{
    /** @use HasFactory<EmployeeStatutoryProfileFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'pf_eligible',
        'pf_uan',
        'esi_eligible',
        'esi_number',
        'professional_tax_state',
        'tax_regime',
        'pan',
        'aadhaar',
        'tan_reference',
    ];

    protected function casts(): array
    {
        return [
            'pf_eligible' => 'boolean',
            'esi_eligible' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
