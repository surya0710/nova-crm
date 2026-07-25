<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\ResourceAllocation;
use App\Models\Task;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

class AuditLogger
{
    public function log(Model $model, string $event, array $properties = [], ?User $user = null): AuditLog
    {
        $organizationId = $model instanceof Organization
            ? $model->id
            : ($model->organization_id
                ?? ($properties['organization_id'] ?? null)
                ?? app(TenantContext::class)->id());

        if ($organizationId === null) {
            throw new \RuntimeException('Cannot write audit log without an organization context.');
        }

        $resolvedUser = $user;
        if ($resolvedUser === null) {
            $authenticated = Auth::user();
            $resolvedUser = $authenticated instanceof User ? $authenticated : null;
        }

        $auditLog = AuditLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $resolvedUser?->id,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'subject' => $this->subjectFor($model),
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);

        $this->maybeNotify($model, $event, $properties, $auditLog);

        return $auditLog;
    }

    protected function subjectFor(Model $model): string
    {
        foreach (['display_name', 'name', 'number', 'title'] as $attribute) {
            if (! empty($model->{$attribute})) {
                return (string) $model->{$attribute};
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    protected function maybeNotify(Model $model, string $event, array $properties, AuditLog $auditLog): void
    {
        if ($event !== 'assigned' || ! isset($properties['to']) || ! $properties['to']) {
            return;
        }

        if (! in_array('assigned_to', $model->getFillable(), true)) {
            return;
        }

        $assignee = User::query()->find($properties['to']);

        if (! $assignee || $assignee->id === Auth::id()) {
            return;
        }

        $assignee->notify(new CrmNotification(
            title: __('New assignment'),
            message: __('You were assigned to :subject', ['subject' => $auditLog->subject]),
            actionUrl: $this->urlFor($model),
            organizationId: $auditLog->organization_id,
        ));
    }

    protected function urlFor(Model $model): ?string
    {
        if ($model instanceof ProgressUpdate) {
            $project = $this->resolveProjectFor($model);

            return $project && Route::has('projects.show')
                ? route('projects.show', $project)
                : null;
        }

        if ($model instanceof ProjectReport) {
            $project = $this->resolveProjectFor($model);

            if ($project && Route::has('projects.reports.download')) {
                return route('projects.reports.download', ['project' => $project, 'report' => $model]);
            }

            return $project && Route::has('projects.show')
                ? route('projects.show', $project)
                : null;
        }

        $map = [
            Lead::class => 'leads.show',
            Customer::class => 'customers.show',
            Opportunity::class => 'pipeline.show',
            Task::class => 'tasks.show',
            Project::class => 'projects.show',
            ResourceAllocation::class => 'resources.allocations.show',
        ];

        $route = $map[$model::class] ?? null;

        return $route ? route($route, $model) : null;
    }

    protected function resolveProjectFor(Model $model): ?Project
    {
        if (! isset($model->project_id) || ! $model->project_id) {
            return null;
        }

        if ($model->relationLoaded('project')) {
            return $model->project;
        }

        return Project::query()->find($model->project_id);
    }
}
