<?php

namespace App\Services\Hrms;

use App\Events\AssetAssigned;
use App\Events\AssetLost;
use App\Events\AssetReturned;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    public function create(array $data, User $actor): EmployeeAsset
    {
        $organization = $this->requireOrganization();

        return DB::transaction(function () use ($data, $actor, $organization): EmployeeAsset {
            $asset = EmployeeAsset::query()->create([
                ...$data,
                'asset_code' => $data['asset_code'] ?? $this->generateAssetCode($organization),
                'status' => $data['status'] ?? 'available',
            ]);

            $this->auditLogger->log($asset, 'asset_created', ['asset_code' => $asset->asset_code], $actor);

            return $asset;
        });
    }

    public function update(EmployeeAsset $asset, array $data, User $actor): EmployeeAsset
    {
        return DB::transaction(function () use ($asset, $data, $actor): EmployeeAsset {
            $before = $asset->only(['name', 'category', 'serial_number', 'status', 'notes']);
            $asset->update($data);

            $this->auditLogger->log($asset, 'asset_updated', [
                'before' => $before,
                'after' => $asset->only(array_keys($before)),
            ], $actor);

            if (($before['status'] ?? null) !== $asset->status) {
                $this->auditLogger->log($asset, 'asset_status_changed', [
                    'from' => $before['status'],
                    'to' => $asset->status,
                ], $actor);
            }

            return $asset;
        });
    }

    public function assign(EmployeeAsset $asset, Employee $employee, array $data, User $actor): EmployeeAsset
    {
        if (! in_array($asset->status, ['available', 'returned'], true)) {
            throw ValidationException::withMessages(['asset' => 'This asset is not available for assignment.']);
        }

        return DB::transaction(function () use ($asset, $employee, $data, $actor): EmployeeAsset {
            $assignedDate = $data['assigned_date'] ?? now()->toDateString();

            $assignment = EmployeeAssetAssignment::query()->create([
                'organization_id' => $asset->organization_id,
                'employee_asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'assigned_date' => $assignedDate,
                'assigned_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $asset->update([
                'employee_id' => $employee->id,
                'assigned_date' => $assignedDate,
                'return_date' => null,
                'status' => 'assigned',
            ]);

            $this->auditLogger->log($asset, 'asset_assigned', [
                'employee_id' => $employee->id,
                'assignment_id' => $assignment->id,
            ], $actor);
            event(AssetAssigned::forModel($asset, [
                'actor_id' => $actor->id,
                'employee_id' => $employee->id,
            ]));

            return $asset->load('employee');
        });
    }

    public function returnAsset(EmployeeAsset $asset, array $data, User $actor): EmployeeAsset
    {
        if ($asset->status !== 'assigned') {
            throw ValidationException::withMessages(['asset' => 'This asset is not currently assigned.']);
        }

        return DB::transaction(function () use ($asset, $data, $actor): EmployeeAsset {
            $returnDate = $data['return_date'] ?? now()->toDateString();

            $assignment = EmployeeAssetAssignment::query()
                ->where('employee_asset_id', $asset->id)
                ->whereNull('return_date')
                ->latest('assigned_date')
                ->first();

            if ($assignment) {
                $assignment->update([
                    'return_date' => $returnDate,
                    'returned_by' => $actor->id,
                    'notes' => $data['notes'] ?? $assignment->notes,
                ]);
            }

            $previousEmployeeId = $asset->employee_id;

            $asset->update([
                'employee_id' => null,
                'return_date' => $returnDate,
                'status' => 'returned',
            ]);

            $this->auditLogger->log($asset, 'asset_returned', [
                'employee_id' => $previousEmployeeId,
                'return_date' => $returnDate,
            ], $actor);
            event(AssetReturned::forModel($asset, [
                'actor_id' => $actor->id,
                'employee_id' => $previousEmployeeId,
            ]));

            return $asset;
        });
    }

    public function markLost(EmployeeAsset $asset, array $data, User $actor): EmployeeAsset
    {
        return DB::transaction(function () use ($asset, $data, $actor): EmployeeAsset {
            if ($asset->status === 'assigned') {
                $assignment = EmployeeAssetAssignment::query()
                    ->where('employee_asset_id', $asset->id)
                    ->whereNull('return_date')
                    ->latest('assigned_date')
                    ->first();

                $assignment?->update([
                    'return_date' => now()->toDateString(),
                    'returned_by' => $actor->id,
                    'notes' => $data['notes'] ?? 'Marked as lost',
                ]);
            }

            $previousEmployeeId = $asset->employee_id;

            $asset->update([
                'status' => 'lost',
                'employee_id' => null,
                'notes' => $data['notes'] ?? $asset->notes,
            ]);

            $this->auditLogger->log($asset, 'asset_lost', [
                'employee_id' => $previousEmployeeId,
            ], $actor);
            event(AssetLost::forModel($asset, [
                'actor_id' => $actor->id,
                'employee_id' => $previousEmployeeId,
            ]));

            return $asset;
        });
    }

    public function markDamaged(EmployeeAsset $asset, array $data, User $actor): EmployeeAsset
    {
        return DB::transaction(function () use ($asset, $data, $actor): EmployeeAsset {
            $before = $asset->status;

            if ($asset->status === 'assigned') {
                $this->returnAsset($asset, ['notes' => $data['notes'] ?? 'Marked as damaged before return'], $actor);
                $asset->refresh();
            }

            $asset->update([
                'status' => 'damaged',
                'notes' => $data['notes'] ?? $asset->notes,
            ]);

            $this->auditLogger->log($asset, 'asset_status_changed', [
                'from' => $before,
                'to' => 'damaged',
            ], $actor);

            return $asset;
        });
    }

    public function retire(EmployeeAsset $asset, array $data, User $actor): EmployeeAsset
    {
        return DB::transaction(function () use ($asset, $data, $actor): EmployeeAsset {
            $before = $asset->status;

            if ($asset->status === 'assigned') {
                $this->returnAsset($asset, ['notes' => $data['notes'] ?? 'Retired'], $actor);
                $asset->refresh();
            }

            $asset->update([
                'status' => 'retired',
                'notes' => $data['notes'] ?? $asset->notes,
            ]);

            $this->auditLogger->log($asset, 'asset_status_changed', [
                'from' => $before,
                'to' => 'retired',
            ], $actor);

            return $asset;
        });
    }

    /** @return array<string, int> */
    public function dashboardStats(): array
    {
        return [
            'total' => EmployeeAsset::query()->count(),
            'assigned' => EmployeeAsset::query()->where('status', 'assigned')->count(),
            'available' => EmployeeAsset::query()->where('status', 'available')->count(),
            'pending_returns' => EmployeeAsset::query()
                ->where('status', 'assigned')
                ->whereNotNull('employee_id')
                ->count(),
        ];
    }

    public function generateAssetCode(Organization $organization): string
    {
        $prefix = (string) config('hrms.asset_code.prefix', 'AST');
        $padding = max(1, (int) config('hrms.asset_code.padding', 5));

        $rows = DB::table('employee_assets')
            ->where('organization_id', $organization->id)
            ->select('asset_code')
            ->lockForUpdate()
            ->get();

        $max = 0;
        foreach ($rows as $row) {
            if (preg_match('/'.preg_quote($prefix, '/').'-(\d+)/', $row->asset_code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix.'-'.str_pad((string) ($max + 1), $padding, '0', STR_PAD_LEFT);
    }

    protected function requireOrganization(): Organization
    {
        $organization = $this->tenantContext->get();
        abort_unless($organization !== null, 422, 'Organization context is required.');

        return $organization;
    }
}
