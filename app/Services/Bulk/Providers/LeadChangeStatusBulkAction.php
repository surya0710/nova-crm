<?php

namespace App\Services\Bulk\Providers;

use App\Contracts\Bulk\BulkActionProviderInterface;
use App\Models\BulkOperation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Bulk\Providers\Concerns\AppliesLeadListingFilters;
use App\Services\Bulk\Providers\Concerns\ResolvesBulkSelection;
use App\Services\LeadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LeadChangeStatusBulkAction implements BulkActionProviderInterface
{
    use AppliesLeadListingFilters;
    use ResolvesBulkSelection;

    public function __construct(
        protected LeadService $leads,
    ) {}

    public function key(): string
    {
        return 'lead.change_status';
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
        return 'Change Status';
    }

    public function permission(): string
    {
        return 'leads.update';
    }

    public function confirmationMessage(): string
    {
        return 'Update the status of the selected leads?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        $options = collect(config('leads.statuses', []))->mapWithKeys(
            fn ($label, $key) => [(string) $key => is_string($label) ? $label : (string) $key]
        )->all();

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
        return $this->resolveLeadQuery($organization, $selection);
    }

    public function executeOne(Model $record, array $input, BulkOperation $operation): array
    {
        /** @var Lead $record */
        $status = (string) ($input['status'] ?? '');
        if ($status === '' || ! array_key_exists($status, config('leads.statuses', []))) {
            return $this->failed('Invalid lead status.');
        }

        if ($record->status === $status) {
            return $this->skipped('Already in this status.');
        }

        $actor = User::query()->find($operation->initiated_by);
        if (! $actor) {
            return $this->failed('Bulk operation actor could not be resolved.');
        }

        try {
            $this->leads->changeStatus($record, $status, $actor);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            return $this->failed($message ?: 'Status update failed.');
        }

        return $this->success();
    }
}
