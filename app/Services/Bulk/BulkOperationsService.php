<?php

namespace App\Services\Bulk;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Jobs\ProcessBulkOperationJob;
use App\Models\BulkOperation;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\TenantContext;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BulkOperationsService
{
    public function __construct(
        protected BulkActionRegistry $registry,
        protected TenantContext $tenant,
        protected AuditLogger $auditLogger,
        protected ModuleSubscriptionService $modules,
    ) {}

    public function failStale(DateTimeInterface $before): int
    {
        return BulkOperation::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [BulkOperation::STATUS_QUEUED, BulkOperation::STATUS_RUNNING])
            ->where('updated_at', '<', $before)
            ->update([
                'status' => BulkOperation::STATUS_FAILED,
                'last_error' => 'Bulk operation exceeded the stale queue work threshold.',
                'completed_at' => now(),
            ]);
    }

    public function reconcileCounters(): int
    {
        $count = 0;
        BulkOperation::query()->withoutGlobalScopes()->eachById(function (BulkOperation $operation) use (&$count): void {
            $processed = min(
                (int) $operation->total_count,
                (int) $operation->success_count + (int) $operation->failed_count + (int) $operation->skipped_count,
            );

            if ((int) $operation->processed_count !== $processed) {
                $operation->forceFill(['processed_count' => $processed])->save();
                $count++;
            }
        });

        return $count;
    }

    /**
     * @param  array{mode?: string, ids?: list<int>, filters?: array<string, mixed>}  $selection
     * @param  array<string, mixed>  $input
     */
    public function start(
        Organization $organization,
        User $actor,
        string $actionKey,
        array $selection,
        array $input = [],
        bool $confirmed = false,
    ): BulkOperation {
        if (! $confirmed) {
            throw ValidationException::withMessages([
                'confirm' => __('You must confirm this bulk action before it can run.'),
            ]);
        }

        $action = $this->registry->resolve($actionKey);
        $this->assertAuthorized($actor, $organization, $action);
        $this->assertLicensed($organization, $action->entityType());

        $query = $action->resolveQuery($organization, $selection);
        $ids = (clone $query)->limit((int) config('bulk.max_selection', 10000) + 1)->pluck(
            $query->getModel()->getKeyName()
        )->map(fn ($id) => (int) $id)->all();

        if (count($ids) === 0) {
            throw ValidationException::withMessages([
                'selection' => __('No matching records were found for this bulk action.'),
            ]);
        }

        if (count($ids) > (int) config('bulk.max_selection', 10000)) {
            throw ValidationException::withMessages([
                'selection' => __('Selection exceeds the maximum of :max records.', [
                    'max' => config('bulk.max_selection', 10000),
                ]),
            ]);
        }

        $this->validateInput($action, $input);

        $operation = BulkOperation::query()->create([
            'organization_id' => $organization->id,
            'initiated_by' => $actor->id,
            'module' => $action->module(),
            'entity_type' => $action->entityType(),
            'action_key' => $action->key(),
            'selection_mode' => $selection['mode'] ?? 'ids',
            'status' => BulkOperation::STATUS_PENDING,
            'total_count' => count($ids),
            'record_ids' => $ids,
            'filters' => $selection['filters'] ?? null,
            'input' => $input,
            'failures' => [],
        ]);

        $this->auditLogger->log($operation, 'bulk_started', [
            'action_key' => $action->key(),
            'entity_type' => $action->entityType(),
            'total_count' => $operation->total_count,
            'selection_mode' => $operation->selection_mode,
        ], $actor);

        $threshold = (int) config('bulk.queue_threshold', 25);

        if ($action->supportsQueue() && $operation->total_count > $threshold) {
            $operation->forceFill(['status' => BulkOperation::STATUS_QUEUED])->save();
            ProcessBulkOperationJob::dispatch($operation->id)->afterCommit();

            $this->auditLogger->log($operation, 'bulk_queued', [
                'total_count' => $operation->total_count,
            ], $actor);

            return $operation->fresh();
        }

        return $this->process($operation->fresh());
    }

    public function process(BulkOperation $operation): BulkOperation
    {
        if ($operation->isTerminal()) {
            return $operation;
        }

        $organization = Organization::query()->findOrFail($operation->organization_id);
        $this->tenant->set($organization);

        $action = $this->registry->resolve($operation->action_key);
        $actor = User::query()->find($operation->initiated_by);

        $operation->forceFill([
            'status' => BulkOperation::STATUS_RUNNING,
            'started_at' => $operation->started_at ?? now(),
            'last_error' => null,
        ])->save();

        $chunkSize = max(1, (int) config('bulk.chunk_size', 50));
        $ids = $operation->record_ids ?? [];
        $input = $operation->input ?? [];
        $failures = $operation->failures ?? [];
        $success = $operation->success_count;
        $failed = $operation->failed_count;
        $skipped = $operation->skipped_count;
        $processed = $operation->processed_count;

        try {
            foreach (array_chunk($ids, $chunkSize) as $chunk) {
                $records = $action->resolveQuery($organization, [
                    'mode' => 'ids',
                    'ids' => $chunk,
                ])->get()->keyBy(fn ($model) => (int) $model->getKey());

                foreach ($chunk as $id) {
                    $record = $records->get((int) $id);
                    if (! $record) {
                        $failed++;
                        $processed++;
                        $failures[] = [
                            'id' => (int) $id,
                            'error' => 'Record not found or not accessible.',
                        ];

                        continue;
                    }

                    try {
                        $result = DB::transaction(function () use ($action, $record, $input, $operation) {
                            return $action->executeOne($record, $input, $operation);
                        });
                    } catch (Throwable $e) {
                        $result = ['status' => 'failed', 'message' => $e->getMessage()];
                    }

                    $processed++;
                    $status = $result['status'] ?? 'failed';

                    if ($status === 'success') {
                        $success++;
                    } elseif ($status === 'skipped') {
                        $skipped++;
                    } else {
                        $failed++;
                        $failures[] = [
                            'id' => (int) $id,
                            'error' => (string) ($result['message'] ?? 'Action failed.'),
                        ];
                    }
                }

                $operation->forceFill([
                    'processed_count' => $processed,
                    'success_count' => $success,
                    'failed_count' => $failed,
                    'skipped_count' => $skipped,
                    'failures' => $failures,
                ])->save();
            }

            $operation->forceFill([
                'status' => BulkOperation::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->log($operation, 'bulk_completed', [
                'action_key' => $operation->action_key,
                'total_count' => $operation->total_count,
                'success_count' => $success,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
                'duration_seconds' => $operation->durationSeconds(),
            ], $actor);
        } catch (Throwable $e) {
            $operation->forceFill([
                'status' => BulkOperation::STATUS_FAILED,
                'last_error' => $e->getMessage(),
                'completed_at' => now(),
                'processed_count' => $processed,
                'success_count' => $success,
                'failed_count' => $failed,
                'skipped_count' => $skipped,
                'failures' => $failures,
            ])->save();

            $this->auditLogger->log($operation, 'bulk_failed', [
                'error' => $e->getMessage(),
            ], $actor);

            throw $e;
        }

        return $operation->fresh();
    }

    public function errorReport(BulkOperation $operation): StreamedResponse
    {
        $filename = 'bulk_'.$operation->id.'_failures.csv';

        return response()->streamDownload(function () use ($operation): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['record_id', 'error']);
            foreach ($operation->failures ?? [] as $row) {
                fputcsv($out, [$row['id'] ?? '', $row['error'] ?? '']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Actions visible to a user for an entity listing.
     *
     * @return list<array<string, mixed>>
     */
    public function availableActionsFor(User $user, Organization $organization, string $entityType): array
    {
        if (! $this->assertLicensedBool($organization, $entityType)) {
            return [];
        }

        $meta = config('bulk.entities.'.$entityType, []);
        $bulkPermission = $meta['bulk_permission'] ?? null;

        $actions = [];
        foreach ($this->registry->forEntity($entityType) as $action) {
            if (! $this->userCanRun($user, $organization, $action, $bulkPermission)) {
                continue;
            }

            $actions[] = [
                'key' => $action->key(),
                'label' => $action->label(),
                'confirmation' => $action->confirmationMessage(),
                'input_fields' => $action->inputFields(),
                'supports_queue' => $action->supportsQueue(),
            ];
        }

        return $actions;
    }

    protected function assertAuthorized(User $actor, Organization $organization, BulkActionProviderInterface $action): void
    {
        $meta = config('bulk.entities.'.$action->entityType(), []);
        $bulkPermission = $meta['bulk_permission'] ?? null;

        if (! $this->userCanRun($actor, $organization, $action, $bulkPermission)) {
            abort(403, 'You are not authorized to run this bulk action.');
        }
    }

    protected function userCanRun(
        User $user,
        Organization $organization,
        BulkActionProviderInterface $action,
        ?string $bulkPermission
    ): bool {
        if ($user->is_super_admin || $user->isOwnerOf($organization)) {
            return true;
        }

        if ($bulkPermission && ! $user->hasPermission($bulkPermission, $organization)) {
            return false;
        }

        return $user->hasPermission($action->permission(), $organization);
    }

    protected function assertLicensed(Organization $organization, string $entityType): void
    {
        if (! $this->assertLicensedBool($organization, $entityType)) {
            throw new InvalidArgumentException("Module is not licensed for entity [{$entityType}].");
        }
    }

    protected function assertLicensedBool(Organization $organization, string $entityType): bool
    {
        $license = config('bulk.entities.'.$entityType.'.license_module');

        return $this->modules->moduleAllowed($organization, $license);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected function validateInput(BulkActionProviderInterface $action, array $input): void
    {
        $errors = [];

        foreach ($action->inputFields() as $field) {
            $key = $field['key'];
            $required = (bool) ($field['required'] ?? false);
            $value = $input[$key] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$key][] = __('The :field field is required.', ['field' => $field['label']]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
