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

class LeadChangeSourceBulkAction implements BulkActionProviderInterface
{
    use AppliesLeadListingFilters;
    use ResolvesBulkSelection;

    public function __construct(
        protected LeadService $leads,
    ) {}

    public function key(): string
    {
        return 'lead.change_source';
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
        return 'Change Source';
    }

    public function permission(): string
    {
        return 'leads.update';
    }

    public function confirmationMessage(): string
    {
        return 'Update the source of the selected leads?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        $options = collect(config('leads.sources', []))->mapWithKeys(
            fn ($label, $key) => [(string) $key => is_string($label) ? $label : (string) $key]
        )->all();

        return [
            [
                'key' => 'source',
                'label' => 'Source',
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
        $source = (string) ($input['source'] ?? '');
        if ($source === '' || ! array_key_exists($source, config('leads.sources', []))) {
            return $this->failed('Invalid lead source.');
        }

        if ($record->source === $source) {
            return $this->skipped('Already using this source.');
        }

        $actor = User::query()->find($operation->initiated_by);
        if (! $actor) {
            return $this->failed('Bulk operation actor could not be resolved.');
        }

        try {
            $this->leads->update($record, ['source' => $source], $actor);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            return $this->failed($message ?: 'Source update failed.');
        }

        return $this->success();
    }
}
