<?php

namespace App\Services\Identity;

use App\Jobs\BulkProvisionEmployeeUsersJob;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserProvisioningBatch;
use App\Services\AuditLogger;
use App\Services\Hrms\EmployeeProvisioningService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class BulkEmployeeUserProvisioningService
{
    public function __construct(
        protected EmployeeProvisioningService $provisioning,
        protected TenantContext $tenantContext,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<int, int>  $employeeIds
     * @param  array{role?: string, send_invitation?: bool, portal_access?: bool}  $options
     */
    public function start(Organization $organization, User $actor, array $employeeIds, array $options = []): UserProvisioningBatch
    {
        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));

        $validIds = Employee::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $employeeIds)
            ->pluck('id')
            ->all();

        $batch = UserProvisioningBatch::query()->create([
            'organization_id' => $organization->id,
            'initiated_by' => $actor->id,
            'status' => 'pending',
            'total' => count($validIds),
            'options' => [
                ...$options,
                'employee_ids' => $validIds,
            ],
        ]);

        $this->auditLogger->log($batch, 'bulk_user_provisioning_started', [
            'total' => $batch->total,
            'options' => $options,
        ], $actor);

        $threshold = (int) config('identity.bulk_sync_threshold', 10);

        if ($batch->total <= $threshold) {
            $this->process($batch);
        } else {
            BulkProvisionEmployeeUsersJob::dispatch($batch->id);
        }

        return $batch->fresh();
    }

    public function process(UserProvisioningBatch $batch): void
    {
        $batch->refresh();
        if (in_array($batch->status, ['completed', 'failed'], true)) {
            return;
        }

        $organization = Organization::query()->findOrFail($batch->organization_id);
        $actor = User::query()->findOrFail($batch->initiated_by);
        $this->tenantContext->set($organization);

        $options = $batch->options ?? [];
        $employeeIds = $options['employee_ids'] ?? [];
        $role = (string) ($options['role'] ?? config('identity.default_employee_role', 'employee'));
        $sendInvitation = (bool) ($options['send_invitation'] ?? true);
        $portalAccess = (bool) ($options['portal_access'] ?? true);
        $chunkSize = max(1, (int) config('identity.bulk_chunk_size', 50));
        $errors = $batch->errors ?? [];

        $batch->markStarted();

        foreach (array_chunk($employeeIds, $chunkSize) as $chunk) {
            $employees = Employee::query()
                ->where('organization_id', $organization->id)
                ->whereIn('id', $chunk)
                ->get()
                ->keyBy('id');

            foreach ($chunk as $employeeId) {
                $employee = $employees->get($employeeId);

                if (! $employee) {
                    $batch->incrementCounters('failed');
                    $errors[] = ['employee_id' => $employeeId, 'error' => 'not_found'];
                    continue;
                }

                if ($employee->user_id) {
                    $batch->incrementCounters('skipped');
                    continue;
                }

                $email = strtolower(trim((string) ($employee->email ?? '')));
                if ($email === '') {
                    $batch->incrementCounters('failed');
                    $errors[] = ['employee_id' => $employeeId, 'error' => 'missing_email'];
                    continue;
                }

                try {
                    DB::transaction(function () use ($employee, $email, $role, $sendInvitation, $portalAccess, $actor) {
                        $this->provisioning->provisionUserForEmployee($employee, [
                            'name' => $employee->full_name,
                            'email' => $email,
                            'role' => $role,
                            'send_invitation' => $sendInvitation,
                            'portal_access' => $portalAccess,
                            'notify' => $sendInvitation,
                        ], $actor);
                    });
                    $batch->incrementCounters('succeeded');
                } catch (\Throwable $e) {
                    $batch->incrementCounters('failed');
                    $errors[] = [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        $batch->refresh();
        $batch->update(['errors' => $errors]);
        $batch->markFinished();

        $this->auditLogger->log($batch, 'bulk_user_provisioning_finished', [
            'succeeded' => $batch->succeeded,
            'skipped' => $batch->skipped,
            'failed' => $batch->failed,
        ], $actor);
    }
}
