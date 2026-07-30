<?php

namespace App\Listeners;

use App\Events\WorkflowDomainEvent;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionLog;
use App\Services\TenantContext;
use App\Workflow\ActionContext;
use App\Workflow\ActionDispatcher;
use App\Workflow\ConditionEvaluator;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class RunTriggeredWorkflows implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    private const EXECUTION_ATTEMPTS = 3;

    public int $tries = 100;

    public int $timeout = 330;

    public string $queue = 'workflows';

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30];

    public function __construct(
        protected TenantContext $tenant,
        protected ConditionEvaluator $conditions,
        protected ActionDispatcher $actions,
        protected WorkflowRuntimeContext $runtime,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(WorkflowDomainEvent $event): array
    {
        return [
            (new WithoutOverlapping('workflow-event-'.$event->organizationId.'-'.$event->eventId))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(WorkflowDomainEvent $event): void
    {
        if ($event->depth > (int) config('workflows.max_depth', 5)) {
            return;
        }

        $previousTenant = $this->tenant->get();
        $organization = Organization::query()->findOrFail($event->organizationId);
        $this->tenant->set($organization);

        try {
            $workflows = Workflow::query()
                ->where('organization_id', $organization->id)
                ->where('status', Workflow::STATUS_ACTIVE)
                ->where('trigger_type', $event->trigger())
                ->with(['rootConditions.childrenRecursive', 'actions'])
                ->get();

            foreach ($workflows as $workflow) {
                if (! $this->run($workflow, $event)) {
                    $this->release(5);

                    return;
                }
            }
        } finally {
            $this->runtime->clear();
            $this->tenant->set($previousTenant);
        }
    }

    public function failed(WorkflowDomainEvent $event, Throwable $exception): void
    {
        $previousTenant = $this->tenant->get();
        $organization = Organization::query()->find($event->organizationId);
        if (! $organization) {
            return;
        }

        $this->tenant->set($organization);
        try {
            WorkflowExecution::query()
                ->where('organization_id', $event->organizationId)
                ->whereIn('status', [WorkflowExecution::STATUS_PENDING, WorkflowExecution::STATUS_RUNNING])
                ->get()
                ->filter(fn (WorkflowExecution $execution) => data_get($execution->trigger_payload, '_event.id') === $event->eventId)
                ->each(function (WorkflowExecution $execution) use ($exception): void {
                    $execution->update([
                        'status' => WorkflowExecution::STATUS_FAILED,
                        'finished_at' => now(),
                        'heartbeat_at' => now(),
                        'lock_owner' => null,
                        'lock_acquired_at' => null,
                        'error_message' => Str::limit($exception->getMessage(), 65000),
                    ]);
                    $this->log($execution, 'execution.job_failed', 'failed', [
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ], 'error');
                });
        } finally {
            $this->tenant->set($previousTenant);
        }
    }

    protected function run(Workflow $workflow, WorkflowDomainEvent $event): bool
    {
        $key = hash('sha256', implode('|', [$event->eventId, $workflow->id]));
        $execution = WorkflowExecution::query()->firstOrCreate(
            [
                'organization_id' => $workflow->organization_id,
                'workflow_id' => $workflow->id,
                'idempotency_key' => $key,
            ],
            [
                'workflow_version' => $workflow->version,
                'trigger_subject_type' => $event->subjectType,
                'trigger_subject_id' => $event->subjectId,
                'trigger_subject_snapshot' => $event->subjectSnapshot,
                'trigger_payload' => [
                    ...$event->payload,
                    '_event' => [
                        'id' => $event->eventId,
                        'trigger' => $event->trigger(),
                        'causation_id' => $event->causationId,
                        'depth' => $event->depth,
                    ],
                ],
                'status' => WorkflowExecution::STATUS_PENDING,
                'queued_at' => now(),
            ],
        );

        if (! $execution->wasRecentlyCreated) {
            if (in_array($execution->status, [
                WorkflowExecution::STATUS_COMPLETED,
                WorkflowExecution::STATUS_SKIPPED,
                WorkflowExecution::STATUS_CANCELLED,
            ], true)) {
                return true;
            }

            if ($execution->status === WorkflowExecution::STATUS_RUNNING) {
                $staleBefore = now()->subSeconds(max(1, (int) $workflow->execution_timeout_seconds));
                $reclaimed = WorkflowExecution::query()
                    ->whereKey($execution->id)
                    ->where('status', WorkflowExecution::STATUS_RUNNING)
                    ->where(function ($query) use ($staleBefore): void {
                        $query->where('heartbeat_at', '<', $staleBefore)
                            ->orWhere(function ($query) use ($staleBefore): void {
                                $query->whereNull('heartbeat_at')->where('lock_acquired_at', '<', $staleBefore);
                            });
                    })
                    ->update([
                        'status' => WorkflowExecution::STATUS_FAILED,
                        'finished_at' => now(),
                        'error_message' => 'Workflow execution lease expired.',
                        'lock_owner' => null,
                        'lock_acquired_at' => null,
                    ]);

                if ($reclaimed === 0) {
                    return true;
                }

                $execution->refresh();
                $this->log($execution, 'execution.lease_recovered', 'pending', [
                    'reason' => 'stale_same_event_lease',
                ], 'warning');
            }

            if ($execution->status === WorkflowExecution::STATUS_FAILED
                && $execution->attempt < self::EXECUTION_ATTEMPTS) {
                $execution->update([
                    'status' => WorkflowExecution::STATUS_PENDING,
                    'finished_at' => null,
                    'error_message' => null,
                ]);
            } elseif ($execution->status !== WorkflowExecution::STATUS_PENDING) {
                return true;
            }
        }

        $lease = (string) Str::uuid();
        $acquired = DB::transaction(function () use ($workflow, $execution, $lease): bool {
            Workflow::query()->whereKey($workflow->id)->lockForUpdate()->firstOrFail();
            $staleBefore = now()->subSeconds(max(1, (int) $workflow->execution_timeout_seconds));
            WorkflowExecution::query()
                ->where('workflow_id', $workflow->id)
                ->where('status', WorkflowExecution::STATUS_RUNNING)
                ->where(function ($query) use ($staleBefore): void {
                    $query->where('heartbeat_at', '<', $staleBefore)
                        ->orWhere(function ($query) use ($staleBefore): void {
                            $query->whereNull('heartbeat_at')->where('lock_acquired_at', '<', $staleBefore);
                        });
                })
                ->update([
                    'status' => WorkflowExecution::STATUS_FAILED,
                    'finished_at' => now(),
                    'error_message' => 'Workflow execution lease expired.',
                    'lock_owner' => null,
                    'lock_acquired_at' => null,
                ]);
            $running = WorkflowExecution::query()
                ->where('workflow_id', $workflow->id)
                ->where('status', WorkflowExecution::STATUS_RUNNING)
                ->count();

            if ($running >= $workflow->concurrency_limit) {
                return false;
            }

            return WorkflowExecution::query()
                ->whereKey($execution->id)
                ->where('status', WorkflowExecution::STATUS_PENDING)
                ->update([
                    'status' => WorkflowExecution::STATUS_RUNNING,
                    'lock_owner' => $lease,
                    'lock_acquired_at' => now(),
                    'heartbeat_at' => now(),
                    'started_at' => now(),
                    'attempt' => DB::raw('attempt + 1'),
                ]) === 1;
        });

        if (! $acquired) {
            return false;
        }

        try {
            $subject = $this->resolveSubject($event);
            $actor = $this->resolveActor($workflow, $event);
            $conditionNodes = WorkflowCondition::withTrashed()
                ->where('workflow_id', $workflow->id)
                ->where('workflow_version', $execution->workflow_version)
                ->whereNull('parent_condition_id')
                ->with('childrenRecursive')
                ->orderBy('position')
                ->get()
                ->all();
            $actionNodes = WorkflowAction::withTrashed()
                ->where('workflow_id', $workflow->id)
                ->where('workflow_version', $execution->workflow_version)
                ->where('status', WorkflowAction::STATUS_ACTIVE)
                ->orderBy('position')
                ->get();
            $this->runtime->enter($event->eventId, $event->depth);
            $this->log($execution, 'execution.started', 'running', ['trigger' => $event->trigger()]);
            $deadline = now()->addSeconds(max(1, (int) $workflow->execution_timeout_seconds));

            $matches = $this->conditions->evaluate(
                $conditionNodes,
                ['subject' => $event->subjectSnapshot, 'payload' => $event->payload, ...$event->subjectSnapshot],
                function (array $condition, bool $result) use ($execution): void {
                    $this->log($execution, 'condition.evaluated', $result ? 'matched' : 'not_matched', [
                        'field' => $condition['field'] ?? null,
                        'operator' => $condition['operator'] ?? null,
                        'value' => $condition['value'] ?? null,
                        'result' => $result,
                    ], conditionId: $condition['id'] ?? null);
                },
            );

            if (! $matches) {
                $execution->update(['status' => WorkflowExecution::STATUS_SKIPPED, 'finished_at' => now(), 'result' => ['conditions_matched' => false]]);
                $this->log($execution, 'execution.skipped', 'skipped', ['reason' => 'conditions_not_matched']);

                return true;
            }

            $completedActionLogs = $execution->logs()
                ->where('event', 'action.completed')
                ->whereNotNull('workflow_action_id')
                ->get()
                ->keyBy(fn (WorkflowExecutionLog $log): int => (int) $log->workflow_action_id);
            $outcomes = $completedActionLogs
                ->mapWithKeys(fn (WorkflowExecutionLog $log): array => [
                    (int) $log->workflow_action_id => $log->context ?? [],
                ])
                ->all();
            foreach ($actionNodes as $action) {
                if ($completedActionLogs->has((int) $action->id)) {
                    continue;
                }
                if (now()->greaterThanOrEqualTo($deadline)) {
                    throw new \RuntimeException('Workflow execution timed out.');
                }
                $execution->update(['current_action_position' => $action->position, 'heartbeat_at' => now()]);
                $this->log($execution, 'action.started', 'running', [
                    'type' => $action->type,
                    'name' => $action->name,
                    'configuration' => $action->configuration,
                ], actionId: $action->id);
                try {
                    $outcomes[$action->id] = DB::transaction(function () use ($execution, $action, $subject, $actor): array {
                        $outcome = $this->actions->dispatch(new ActionContext($execution, $action, $subject, $actor));
                        $this->log($execution, 'action.completed', 'completed', $outcome, actionId: $action->id);

                        return $outcome;
                    });
                } catch (Throwable $exception) {
                    $this->log($execution, 'action.failed', 'failed', [
                        'type' => $action->type,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ], 'error', actionId: $action->id);
                    throw $exception;
                }
            }

            $execution->update([
                'status' => WorkflowExecution::STATUS_COMPLETED,
                'finished_at' => now(),
                'heartbeat_at' => now(),
                'result' => ['conditions_matched' => true, 'actions' => $outcomes],
            ]);
            $this->log($execution, 'execution.completed', 'completed');

            return true;
        } catch (Throwable $exception) {
            $execution->update([
                'status' => WorkflowExecution::STATUS_FAILED,
                'finished_at' => now(),
                'heartbeat_at' => now(),
                'error_message' => Str::limit($exception->getMessage(), 65000),
            ]);
            $this->log($execution, 'execution.failed', 'failed', ['exception' => $exception::class, 'message' => $exception->getMessage()], 'error');
            throw $exception;
        } finally {
            $execution->update(['lock_owner' => null, 'lock_acquired_at' => null]);
            $this->runtime->clear();
        }
    }

    protected function resolveSubject(WorkflowDomainEvent $event): Model
    {
        $class = Relation::getMorphedModel($event->subjectType) ?? $event->subjectType;
        if (! is_a($class, Model::class, true)) {
            throw new \UnexpectedValueException('Workflow trigger subject is not a model.');
        }

        return $class::withoutGlobalScopes()
            ->where('organization_id', $event->organizationId)
            ->whereKey($event->subjectId)
            ->firstOrFail();
    }

    protected function resolveActor(Workflow $workflow, WorkflowDomainEvent $event): User
    {
        $actorId = (int) ($event->payload['actor_id'] ?? $workflow->created_by ?? 0);
        $actor = $workflow->organization->users()->whereKey($actorId)->first()
            ?? $workflow->organization->users()->orderBy('users.id')->first();

        if (! $actor) {
            throw new \RuntimeException('Workflow organization has no user available to own actions.');
        }

        return $actor;
    }

    protected function log(
        WorkflowExecution $execution,
        string $event,
        ?string $status = null,
        array $context = [],
        string $level = 'info',
        ?int $actionId = null,
        ?int $conditionId = null,
    ): void {
        WorkflowExecutionLog::query()->create([
            'organization_id' => $execution->organization_id,
            'workflow_execution_id' => $execution->id,
            'workflow_action_id' => $actionId,
            'workflow_condition_id' => $conditionId,
            'level' => $level,
            'event' => $event,
            'status' => $status,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
