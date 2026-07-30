<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportTemplateProviderInterface;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportOwnerResolver;
use App\Services\Import\ImportTemplateColumn;
use App\Services\Import\ImportTemplateLookupGroup;
use App\Services\MetadataEntityFormService;

/**
 * Lead-specific import template content for ImportTemplateService.
 *
 * Column labels match Lead Import detection labels exactly (no aliases).
 * Metadata and lookups are resolved from the current tenant only.
 */
class LeadImportTemplateAdapter implements ImportTemplateProviderInterface
{
    public function __construct(
        protected MetadataEntityFormService $metadataForms,
        protected ImportOwnerResolver $owners,
    ) {}

    public function entityType(): string
    {
        return 'lead';
    }

    public function entityLabel(): string
    {
        return 'Lead';
    }

    public function dataSheetName(): string
    {
        return 'Lead Import';
    }

    public function columns(Organization $organization): array
    {
        return array_merge($this->standardColumns(), $this->metadataColumns($organization));
    }

    public function sampleValues(Organization $organization): array
    {
        $owner = $this->owners->listMembers($organization)->first();
        $ownerSample = $owner instanceof User
            ? (string) $owner->name
            : 'John Smith';

        $samples = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+919876543210',
            'company' => 'ABC Pvt Ltd',
            'address_line_1' => '12 Connaught Place',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'country' => 'India',
            'postal_code' => '110001',
            'status' => 'New',
            'source' => 'Website',
            'owner' => $ownerSample,
            'notes' => 'Interested in a product demo',
        ];

        foreach ($this->metadataForms->fieldsFor($organization, 'lead', 'create') as $item) {
            /** @var MetadataFieldDefinition $definition */
            $definition = $item['field'];
            if ($this->isReservedStandardKey($definition->key)) {
                continue;
            }

            $samples[$definition->key] = $this->sampleForMetadataDefinition($definition);
        }

        return $samples;
    }

    public function lookupGroups(Organization $organization): array
    {
        $groups = [
            new ImportTemplateLookupGroup(
                heading: 'Status',
                values: array_values(config('leads.statuses', [])),
                note: 'Use the label or key in the Status column.',
            ),
            new ImportTemplateLookupGroup(
                heading: 'Source',
                values: array_values(config('leads.sources', [])),
                note: 'Use the label or key in the Source column.',
            ),
            new ImportTemplateLookupGroup(
                heading: 'Owner',
                values: $this->owners->listMembers($organization)
                    ->map(static fn (User $user): string => $user->name.' <'.$user->email.'>')
                    ->values()
                    ->all(),
                note: 'Match Owner by member email or full name. Leave blank to use Assignment rules.',
            ),
        ];

        foreach ($this->metadataForms->fieldsFor($organization, 'lead', 'create') as $item) {
            /** @var MetadataFieldDefinition $definition */
            $definition = $item['field'];
            if ($this->isReservedStandardKey($definition->key)) {
                continue;
            }

            $type = (string) $definition->type;

            if ($definition->isOptionBacked()) {
                $values = $definition->options
                    ->where('is_active', true)
                    ->map(static function ($option): string {
                        $value = (string) $option->value;
                        $label = (string) ($option->label ?: $value);

                        return $label === $value ? $value : "{$label} [{$value}]";
                    })
                    ->filter(static fn (string $value): bool => $value !== '')
                    ->values()
                    ->all();

                $groups[] = new ImportTemplateLookupGroup(
                    heading: (string) $definition->label,
                    values: $values,
                    note: 'Metadata dropdown — enter the option value (shown in brackets when label differs).',
                );

                continue;
            }

            if (in_array($type, ['date', 'datetime'], true)) {
                $groups[] = new ImportTemplateLookupGroup(
                    heading: (string) $definition->label,
                    values: ['2026-07-17'],
                    note: 'Date format example: YYYY-MM-DD',
                );

                continue;
            }

            if (in_array($type, ['number', 'decimal', 'currency', 'percentage'], true)) {
                $groups[] = new ImportTemplateLookupGroup(
                    heading: (string) $definition->label,
                    values: ['100'],
                    note: 'Enter a numeric value.',
                );

                continue;
            }

            $groups[] = new ImportTemplateLookupGroup(
                heading: (string) $definition->label,
                values: [],
                note: 'Free-text metadata field — enter any text value.',
            );
        }

        return $groups;
    }

    public function instructionLines(Organization $organization): array
    {
        $maxKb = (int) config('import.max_upload_kilobytes', 10240);
        $metadataCount = count($this->metadataColumns($organization));

        return [
            'Download this template, fill in your lead rows, then upload it from Import Leads.',
            'Required: provide a name using First Name and/or Last Name (or a Full Name column if you add one).',
            'Optional standard fields: Email, Phone, Company, Address, City, State, Country, Postal Code, Status, Source, Owner, Notes.',
            'Email and Phone should be unique within the file and against existing open leads in your organization.',
            'Duplicate emails or phones are reported in preview and are skipped on import (not merged).',
            'Owner matching: use an organization member email or exact full name.',
            'If Owner is blank, the Assignment Platform assigns an owner using your active rules and pools.',
            'Status and Source must match one of the values on the Lookup Values sheet (label or key).',
            'Supported formats: CSV and XLSX. Maximum file size: '.$maxKb.' KB.',
            $metadataCount > 0
                ? 'This organization has '.$metadataCount.' active lead metadata field(s). Columns appear after the standard fields — see Lookup Values for dropdown options and formats.'
                : 'No active lead metadata fields are configured for this organization.',
            'Do not rename header columns. Keep the header row as the first row.',
            'Delete the sample row before importing, or leave it if it is valid test data you want imported.',
        ];
    }

    /**
     * @return list<ImportTemplateColumn>
     */
    protected function standardColumns(): array
    {
        return [
            new ImportTemplateColumn(key: 'first_name', label: 'First Name'),
            new ImportTemplateColumn(key: 'last_name', label: 'Last Name'),
            new ImportTemplateColumn(key: 'email', label: 'Email'),
            new ImportTemplateColumn(key: 'phone', label: 'Phone'),
            new ImportTemplateColumn(key: 'company', label: 'Company'),
            new ImportTemplateColumn(key: 'address_line_1', label: 'Address'),
            new ImportTemplateColumn(key: 'city', label: 'City'),
            new ImportTemplateColumn(key: 'state', label: 'State'),
            new ImportTemplateColumn(key: 'country', label: 'Country'),
            new ImportTemplateColumn(key: 'postal_code', label: 'Postal Code'),
            new ImportTemplateColumn(key: 'status', label: 'Status'),
            new ImportTemplateColumn(key: 'source', label: 'Source'),
            new ImportTemplateColumn(key: 'owner', label: 'Owner'),
            new ImportTemplateColumn(key: 'notes', label: 'Notes'),
        ];
    }

    /**
     * @return list<ImportTemplateColumn>
     */
    protected function metadataColumns(Organization $organization): array
    {
        $columns = [];

        foreach ($this->metadataForms->fieldsFor($organization, 'lead', 'create') as $item) {
            /** @var MetadataFieldDefinition $definition */
            $definition = $item['field'];
            if ($this->isReservedStandardKey($definition->key)) {
                continue;
            }

            $columns[] = new ImportTemplateColumn(
                key: $definition->key,
                label: $definition->label,
                required: (bool) $definition->is_required,
                isMetadata: true,
                metadataType: (string) $definition->type,
            );
        }

        return $columns;
    }

    protected function isReservedStandardKey(string $key): bool
    {
        return in_array($key, [
            'first_name', 'last_name', 'full_name', 'email', 'phone', 'company',
            'address_line_1', 'city', 'state', 'country', 'postal_code',
            'source', 'status', 'owner', 'priority', 'industry', 'budget', 'notes',
        ], true);
    }

    protected function sampleForMetadataDefinition(MetadataFieldDefinition $definition): string
    {
        $type = (string) $definition->type;

        if ($definition->isOptionBacked()) {
            $option = $definition->options->firstWhere('is_active', true)
                ?? $definition->options->first();

            if ($option) {
                return (string) $option->value;
            }
        }

        return match (true) {
            in_array($type, ['date', 'datetime'], true) => '2026-07-17',
            in_array($type, ['number', 'decimal', 'currency', 'percentage'], true) => '100',
            $type === 'boolean' => 'Yes',
            $type === 'email' => 'sample@example.com',
            $type === 'phone' => '+919876543210',
            $type === 'url' => 'https://example.com',
            default => 'Sample value',
        };
    }
}
