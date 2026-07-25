<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Organization;
use App\Models\Task;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskChangePriorityBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function key(): string
    {
        return 'task.change_priority';
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
        return 'Change Priority';
    }

    public function permission(): string
    {
        return 'tasks.manage-priority';
    }

    public function confirmationMessage(): string
    {
        return 'Update priority for the selected tasks?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        $options = collect(config('tasks.priorities', []))
            ->mapWithKeys(fn ($label, $key) => [(string) $key => is_string($label) ? $label : (string) $key])
            ->all();

        if ($options === []) {
            $options = [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent',
            ];
        }

        return [
            [
                'key' => 'priority',
                'label' => 'Priority',
                'type' => 'select',
                'required' => true,
                'options' => $options,
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
        $priority = (string) ($input['priority'] ?? '');
        if ($priority === '') {
            return $this->failed('Priority is required.');
        }

        if (($record->priority ?? null) === $priority) {
            return $this->skipped('Already at this priority.');
        }

        $record->forceFill(['priority' => $priority])->save();

        return $this->success();
    }
}
