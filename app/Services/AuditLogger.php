<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\CrmNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(Model $model, string $event, array $properties = [], ?User $user = null): AuditLog
    {
        $organizationId = $model->organization_id ?? app(TenantContext::class)->id();

        $auditLog = AuditLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => ($user ?? Auth::user())?->id,
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
        $map = [
            \App\Models\Lead::class => 'leads.show',
            \App\Models\Customer::class => 'customers.show',
            \App\Models\Opportunity::class => 'pipeline.show',
            \App\Models\Task::class => 'tasks.show',
        ];

        $route = $map[$model::class] ?? null;

        return $route ? route($route, $model) : null;
    }
}
