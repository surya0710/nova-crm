<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEmergencyContact extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'name',
        'relationship',
        'phone',
        'alternate_mobile',
        'email',
        'address',
        'is_primary',
    ];

    public function getMobileAttribute(): ?string
    {
        return $this->phone;
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
