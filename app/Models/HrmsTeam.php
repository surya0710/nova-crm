<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\HrmsTeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmsTeam extends Model
{
    /** @use HasFactory<HrmsTeamFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $table = 'hrms_teams';

    protected $fillable = [
        'organization_id',
        'department_id',
        'team_lead_employee_id',
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'team_lead_employee_id');
    }
}
