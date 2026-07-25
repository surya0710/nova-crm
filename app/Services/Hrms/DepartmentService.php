<?php

namespace App\Services\Hrms;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(array $data, User $actor): Department
    {
        return DB::transaction(function () use ($data, $actor): Department {
            $department = Department::query()->create($data);
            $this->auditLogger->log($department, 'department_created', ['name' => $department->name], $actor);

            return $department;
        });
    }

    public function update(Department $department, array $data, User $actor): Department
    {
        return DB::transaction(function () use ($department, $data, $actor): Department {
            if (isset($data['parent_id']) && (int) $data['parent_id'] === $department->id) {
                throw ValidationException::withMessages(['parent_id' => 'A department cannot be its own parent.']);
            }

            $before = $department->only(['name', 'code', 'parent_id', 'is_active', 'description']);
            $department->update($data);
            $this->auditLogger->log($department, 'department_updated', [
                'before' => $before,
                'after' => $department->only(array_keys($before)),
            ], $actor);

            return $department;
        });
    }
}
