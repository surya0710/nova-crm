<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEducation extends Model
{
    use Auditable, BelongsToOrganization;

    protected $table = 'employee_educations';

    protected $fillable = [
        'organization_id',
        'employee_id',
        'institution',
        'degree',
        'field_of_study',
        'start_date',
        'end_date',
        'start_year',
        'end_year',
        'grade',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_year' => 'integer',
            'end_year' => 'integer',
        ];
    }

    public function getSpecializationAttribute(): ?string
    {
        return $this->field_of_study;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
