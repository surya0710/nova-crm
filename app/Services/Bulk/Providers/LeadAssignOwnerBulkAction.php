<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeadAssignOwnerBulkAction implements BulkActionProviderInterface
{
    use ResolvesBulkSelection;

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
            [
                'key' => 'owner_id',
                'label' => 'Owner User ID',
                'type' => 'integer',
                'required' => true,
            ],
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
        $owner = User::query()->find($ownerId);

        if (! $owner || ! $owner->organizations()->where('organizations.id', $operation->organization_id)->exists()) {
            return $this->failed('Owner is not a member of this organization.');
        }

        if ((int) $record->assigned_to === $ownerId) {
            return $this->skipped('Already assigned to this owner.');
        }

        $record->forceFill(['assigned_to' => $ownerId])->save();

        return $this->success();
    }
}
