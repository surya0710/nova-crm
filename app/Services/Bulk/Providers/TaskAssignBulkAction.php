<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskAssignBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function key(): string
    {
        return 'task.assign';
    }

    public function module(): string
    {
        return 'projects';
    }

    public function entityType(): string
    {
        return 'task';
    }

    public function label(): string
    {
        return 'Assign User';
    }

    public function permission(): string
    {
        return 'tasks.assign';
    }

    public function confirmationMessage(): string
    {
        return 'Assign the selected tasks to this user?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [
            [
                'key' => 'assigned_to',
                'label' => 'Assignee user ID',
                'type' => 'number',
                'required' => false,
            ],
        ];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery(Task::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Task $record */
        $userId = isset($input['assigned_to']) && $input['assigned_to'] !== ''
            ? (int) $input['assigned_to']
            : null;

        $assignee = $userId ? User::query()->find($userId) : null;
        $actor = User::query()->find($operation->initiated_by) ?? $assignee;

        if (! $actor) {
            return $this->failed('Actor missing.');
        }

        app(TaskService::class)->assign($record, $assignee, $actor);

        return $this->success();
    }
}
