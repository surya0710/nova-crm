<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Assignment\AssignmentService;
use App\Services\Bulk\Concerns\DefinesLookupField;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LeadAssignOwnerBulkAction implements BulkActionProviderInterface
{
    use DefinesLookupField;
    use ResolvesBulkSelection;

    public function __construct(
        protected AssignmentService $assignments,
    ) {}

    public function key(): string
    {
        return 'lead.assign_owner';
    }

    public function module(): string
    {
        return 'crm';
    }

    public function entityType(): string
    {
        return 'lead';
    }

    public function label(): string
    {
        return 'Assign Owner';
    }

    public function permission(): string
    {
        return 'leads.update';
    }

    public function confirmationMessage(): string
    {
        return 'Assign the selected leads to the chosen owner?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [
            $this->userField('owner_id', 'Assign Owner'),
        ];
    }

    public function resolveQuery(Organization $organization, array $selection): Builder
    {
        return $this->baseOrganizationQuery(Lead::class, $organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Lead $record */
        $ownerId = (int) ($input['owner_id'] ?? 0);

        if ($ownerId <= 0) {
            return $this->failed('A valid owner must be selected.');
        }

        if ((int) $record->assigned_to === $ownerId) {
            return $this->skipped('Already assigned to this owner.');
        }

        $actor = User::query()->find($operation->initiated_by);
        if (! $actor) {
            return $this->failed('Bulk operation actor could not be resolved.');
        }

        try {
            $this->assignments->assignOwner($record, $ownerId, $actor);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            return $this->failed($message ?: 'Owner assignment failed.');
        }

        return $this->success();
    }
}
