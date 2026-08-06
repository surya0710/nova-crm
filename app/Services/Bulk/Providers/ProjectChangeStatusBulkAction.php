<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProjectChangeStatusBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

    public function key(): string
    {
        return 'project.change_status';
    }

    public function module(): string
    {
        return 'projects';
    }

    public function entityType(): string
    {
        return 'project';
    }

    public function label(): string
    {
        return 'Change Status';
    }

    public function permission(): string
    {
        return 'projects.edit';
    }

    public function confirmationMessage(): string
    {
        return 'Update status for the selected projects?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        $options = collect(config('projects.statuses', config('project.statuses', [])))
            ->mapWithKeys(fn ($label, $key) => [(string) $key => is_string($label) ? $label : (string) $key])
            ->all();

        if ($options === []) {
            $options = [
                'planned' => 'Planned',
                'active' => 'Active',
                'on_hold' => 'On Hold',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ];
        }

        return [
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'required' => true,
                'options' => $options,
            ],
        ];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery(Project::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Project $record */
        $status = (string) ($input['status'] ?? '');
        if ($status === '') {
            return $this->failed('Status is required.');
        }

        if (($record->status ?? null) === $status) {
            return $this->skipped('Already in this status.');
        }

        $record->forceFill(['status' => $status])->save();

        return $this->success();
    }
}
