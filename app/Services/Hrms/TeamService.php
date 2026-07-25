<?php

namespace App\Services\Hrms;

use App\Models\HrmsTeam;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(array $data, User $actor): HrmsTeam
    {
        return DB::transaction(function () use ($data, $actor): HrmsTeam {
            $team = HrmsTeam::query()->create($data);
            $this->auditLogger->log($team, 'team_created', ['name' => $team->name], $actor);

            return $team;
        });
    }

    public function update(HrmsTeam $team, array $data, User $actor): HrmsTeam
    {
        return DB::transaction(function () use ($team, $data, $actor): HrmsTeam {
            $before = $team->only(['name', 'code', 'department_id', 'team_lead_employee_id', 'is_active']);
            $team->update($data);
            $this->auditLogger->log($team, 'team_updated', [
                'before' => $before,
                'after' => $team->only(array_keys($before)),
            ], $actor);

            return $team;
        });
    }
}
