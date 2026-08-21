<?php

namespace App\Services;

use App\Events\CustomerLifecycleChanged;
use App\Events\CustomerUpdated;
use App\Events\WorkflowDomainEvent;
use App\Models\Customer;
use App\Models\CustomerLifecycleMilestone;
use App\Models\Invoice;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerLifecycleService
{
    public function handle(WorkflowDomainEvent $event): ?CustomerLifecycleMilestone
    {
        $subject = $this->subjectFrom($event);
        if (! $subject) {
            return null;
        }

        $customer = $this->customerFrom($subject);
        if (! $customer) {
            return null;
        }

        $actor = $this->actorFrom($event, $customer);

        return $this->applyMilestone($customer, $event->trigger(), $subject, $actor);
    }

    public function applyMilestone(
        Customer $customer,
        string $milestone,
        ?Model $source = null,
        ?User $actor = null,
    ): ?CustomerLifecycleMilestone {
        $milestones = config('customer_lifecycle.milestones', []);
        $targetStage = $milestones[$milestone] ?? null;
        $fromStage = $customer->lifecycle_stage;

        return DB::transaction(function () use ($customer, $milestone, $targetStage, $fromStage, $source, $actor) {
            $existing = CustomerLifecycleMilestone::query()
                ->where('organization_id', $customer->organization_id)
                ->where('customer_id', $customer->id)
                ->where('milestone', $milestone)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $toStage = $this->resolveTargetStage($fromStage, is_string($targetStage) ? $targetStage : null);

            if ($toStage && $toStage !== $fromStage) {
                $customer->update(['lifecycle_stage' => $toStage]);
                $customer = $customer->fresh();
            }

            $record = CustomerLifecycleMilestone::query()->create([
                'organization_id' => $customer->organization_id,
                'customer_id' => $customer->id,
                'milestone' => $milestone,
                'from_stage' => $fromStage,
                'to_stage' => $toStage ?? $fromStage,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'applied_at' => now(),
            ]);

            if ($toStage && $toStage !== $fromStage && $actor) {
                $runtime = app(WorkflowRuntimeContext::class);
                event(CustomerUpdated::forModel(
                    $customer,
                    ['actor_id' => $actor->id, 'changes' => ['lifecycle_stage'], 'milestone' => $milestone],
                    causationId: $runtime->causationId ?? $record->id,
                    depth: $runtime->causationId ? $runtime->depth + 1 : 1,
                ));
                event(CustomerLifecycleChanged::forModel(
                    $customer,
                    [
                        'actor_id' => $actor->id,
                        'from' => $fromStage,
                        'to' => $toStage,
                        'milestone' => $milestone,
                    ],
                    causationId: $runtime->causationId ?? $record->id,
                    depth: $runtime->causationId ? $runtime->depth + 1 : 1,
                ));
            }

            return $record;
        });
    }

    public function changeStage(Customer $customer, string $stage, User $actor): Customer
    {
        if (! array_key_exists($stage, config('customers.lifecycle_stages', []))) {
            return $customer;
        }

        if ($customer->lifecycle_stage === $stage) {
            return $customer;
        }

        $fromStage = $customer->lifecycle_stage;
        $customer = app(CustomerService::class)->update($customer, ['lifecycle_stage' => $stage], $actor);

        if ($customer->lifecycle_stage !== $fromStage) {
            $runtime = app(WorkflowRuntimeContext::class);
            event(CustomerLifecycleChanged::forModel(
                $customer,
                [
                    'actor_id' => $actor->id,
                    'from' => $fromStage,
                    'to' => $customer->lifecycle_stage,
                ],
                causationId: $runtime->causationId,
                depth: $runtime->causationId ? $runtime->depth + 1 : 0,
            ));
        }

        return $customer;
    }

    public function customerFrom(Model $subject): ?Customer
    {
        return match (true) {
            $subject instanceof Customer => $subject,
            $subject instanceof Opportunity,
            $subject instanceof Quotation,
            $subject instanceof SalesOrder,
            $subject instanceof Invoice,
            $subject instanceof Payment => $subject->customer,
            default => isset($subject->customer_id)
                ? Customer::query()->find($subject->customer_id)
                : null,
        };
    }

    protected function resolveTargetStage(?string $current, ?string $target): ?string
    {
        if (! $target) {
            return $current;
        }

        $order = array_values(config('customer_lifecycle.stages', []));
        $currentRank = array_search($current, $order, true);
        $targetRank = array_search($target, $order, true);

        if ($targetRank === false) {
            return $current;
        }

        if ($currentRank === false || $targetRank > $currentRank) {
            return $target;
        }

        return $current;
    }

    protected function subjectFrom(WorkflowDomainEvent $event): ?Model
    {
        $class = $event->subjectType;
        $map = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
        if (isset($map[$class])) {
            $class = $map[$class];
        }

        if (! class_exists($class)) {
            return null;
        }

        return $class::query()->find($event->subjectId);
    }

    protected function actorFrom(WorkflowDomainEvent $event, Customer $customer): ?User
    {
        $actorId = (int) ($event->payload['actor_id'] ?? 0);
        if ($actorId > 0) {
            return User::query()->find($actorId);
        }

        return $customer->creator ?? $customer->assignee;
    }
}
