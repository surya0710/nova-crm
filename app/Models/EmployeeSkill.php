<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSkill extends Model
{
    use Auditable, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'skill',
        'proficiency',
        'years_of_experience',
        'last_used',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'last_used' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getProficiencyLabelAttribute(): string
    {
        return config('hrms.skill_proficiencies.'.$this->proficiency, ucfirst((string) $this->proficiency));
    }
}
