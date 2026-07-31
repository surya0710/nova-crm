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

class LeadTagsBulkAction implements BulkActionProviderInterface
{
    use AppliesLeadListingFilters;
    use ResolvesBulkSelection;

    public function __construct(
        protected LeadService $leads,
        protected string $mode,
    ) {}

    public function key(): string
    {
        return $this->mode === 'remove' ? 'lead.remove_tags' : 'lead.add_tags';
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
        return $this->mode === 'remove' ? 'Remove Tags' : 'Add Tags';
    }

    public function permission(): string
    {
        return 'leads.update';
    }

    public function confirmationMessage(): string
    {
        return $this->mode === 'remove'
            ? 'Remove the listed tags from the selected leads?'
            : 'Add the listed tags to the selected leads?';
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function inputFields(): array
    {
        return [
            [
                'key' => 'tags',
                'label' => 'Tags (comma-separated)',
                'type' => 'text',
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
        $tags = $this->parseTags($input['tags'] ?? null);
        if ($tags === []) {
            return $this->failed('At least one tag is required.');
        }

        $current = array_values(array_filter(array_map('strval', $record->tags ?? [])));

        if ($this->mode === 'remove') {
            $next = array_values(array_filter($current, fn (string $tag) => ! in_array($tag, $tags, true)));
            if ($next === $current) {
                return $this->skipped('None of the listed tags were present.');
            }
        } else {
            $next = array_values(array_unique([...$current, ...$tags]));
            if ($next === $current) {
                return $this->skipped('All listed tags were already present.');
            }
        }

        $actor = User::query()->find($operation->initiated_by);
        if (! $actor) {
            return $this->failed('Bulk operation actor could not be resolved.');
        }

        try {
            $this->leads->update($record, ['tags' => $next === [] ? null : $next], $actor);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            return $this->failed($message ?: 'Tag update failed.');
        }

        return $this->success();
    }

    /**
     * @return list<string>
     */
    protected function parseTags(mixed $raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = preg_split('/\s*,\s*/', trim((string) $raw)) ?: [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($tag) => trim((string) $tag), $parts),
            static fn (string $tag) => $tag !== '' && mb_strlen($tag) <= 50,
        )));
    }

    public static function add(): self
    {
        return new self(app(LeadService::class), 'add');
    }

    public static function remove(): self
    {
        return new self(app(LeadService::class), 'remove');
    }
}
