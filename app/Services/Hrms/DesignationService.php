<?php

namespace App\Services\Hrms;

use App\Models\Designation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class DesignationService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(array $data, User $actor): Designation
    {
        return DB::transaction(function () use ($data, $actor): Designation {
            $designation = Designation::query()->create($data);
            $this->auditLogger->log($designation, 'designation_created', ['name' => $designation->name], $actor);

            return $designation;
        });
    }

    public function update(Designation $designation, array $data, User $actor): Designation
    {
        return DB::transaction(function () use ($designation, $data, $actor): Designation {
            $before = $designation->only(['name', 'code', 'department_id', 'is_active', 'description']);
            $designation->update($data);
            $this->auditLogger->log($designation, 'designation_updated', [
                'before' => $before,
                'after' => $designation->only(array_keys($before)),
            ], $actor);

            return $designation;
        });
    }
}
