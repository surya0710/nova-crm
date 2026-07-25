<?php

namespace App\Services\Hrms;

use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class BranchService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(array $data, User $actor): Branch
    {
        return DB::transaction(function () use ($data, $actor): Branch {
            if (! empty($data['is_default'])) {
                $this->clearDefaultFlags();
            }

            $branch = Branch::query()->create($data);
            $this->auditLogger->log($branch, 'branch_created', [
                'name' => $branch->name,
                'is_default' => $branch->is_default,
            ], $actor);

            return $branch;
        });
    }

    public function update(Branch $branch, array $data, User $actor): Branch
    {
        return DB::transaction(function () use ($branch, $data, $actor): Branch {
            if (! empty($data['is_default'])) {
                $this->clearDefaultFlags($branch->id);
            }

            $before = $branch->only([
                'name', 'code', 'is_active', 'is_default', 'contact_email', 'contact_phone', 'manager_employee_id',
            ]);
            $branch->update($data);
            $this->auditLogger->log($branch, 'branch_updated', [
                'before' => $before,
                'after' => $branch->only(array_keys($before)),
            ], $actor);

            return $branch;
        });
    }

    protected function clearDefaultFlags(?int $exceptId = null): void
    {
        $query = Branch::query()->where('is_default', true);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }
}
