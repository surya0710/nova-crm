<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Contracts\Import\ImportTemplateProviderInterface;
use App\Models\Organization;
use App\Services\Import\ImportFieldDefinition;
use App\Services\Import\ImportTemplateColumn;
use App\Services\Import\ImportTemplateLookupGroup;

/**
 * Generic template provider that derives columns from any ImportableEntityInterface.
 */
class GenericImportTemplateAdapter implements ImportTemplateProviderInterface
{
    public function __construct(protected ImportableEntityInterface $entity) {}

    public function entityType(): string
    {
        return $this->entity->entityType();
    }

    public function entityLabel(): string
    {
        return $this->entity->entityLabel();
    }

    public function dataSheetName(): string
    {
        return $this->entity->entityLabel().' Import';
    }

    public function columns(Organization $organization): array
    {
        return array_map(
            static fn (ImportFieldDefinition $field): ImportTemplateColumn => new ImportTemplateColumn(
                key: $field->key,
                label: $field->label,
                required: $field->required,
            ),
            $this->entity->fieldDefinitions()
        );
    }

    public function sampleValues(Organization $organization): array
    {
        $samples = [];

        foreach ($this->entity->fieldDefinitions() as $field) {
            $samples[$field->key] = $this->sampleForField($field);
        }

        return $samples;
    }

    public function lookupGroups(Organization $organization): array
    {
        $groups = [];

        foreach ($this->entity->fieldDefinitions() as $field) {
            if ($field->dataType === ImportFieldDefinition::TYPE_BOOLEAN) {
                $groups[] = new ImportTemplateLookupGroup(
                    heading: $field->label,
                    values: ['Yes', 'No'],
                    note: 'Accepted values: yes, true, 1, y (and their false equivalents).',
                );
            }
        }

        return $groups;
    }

    public function instructionLines(Organization $organization): array
    {
        $maxKb = (int) config('import.max_upload_kilobytes', 10240);
        $requiredFields = array_values(array_map(
            static fn (ImportFieldDefinition $field): string => $field->label,
            array_filter(
                $this->entity->fieldDefinitions(),
                static fn (ImportFieldDefinition $field): bool => $field->required
            )
        ));

        return [
            'Download this template, fill in your '.$this->entity->entityLabel().' rows, then upload it from the Import Center.',
            $requiredFields !== []
                ? 'Required fields: '.implode(', ', $requiredFields).'.'
                : 'No required fields are defined for this entity.',
            'Duplicate handling follows the selected import strategy (skip, update, or create).',
            'Supported formats: CSV and XLSX. Maximum file size: '.$maxKb.' KB.',
            'Do not rename header columns. Keep the header row as the first row.',
            'Delete the sample row before importing, or leave it if it is valid test data you want imported.',
        ];
    }

    protected function sampleForField(ImportFieldDefinition $field): string
    {
        return match ($field->dataType) {
            ImportFieldDefinition::TYPE_EMAIL => 'sample@example.com',
            ImportFieldDefinition::TYPE_PHONE => '+919876543210',
            ImportFieldDefinition::TYPE_DATE => '2026-07-17',
            ImportFieldDefinition::TYPE_NUMBER => '100',
            ImportFieldDefinition::TYPE_INTEGER => '1',
            ImportFieldDefinition::TYPE_BOOLEAN => 'Yes',
            default => match ($field->key) {
                'first_name' => 'John',
                'last_name' => 'Doe',
                'name' => 'Sample '.$field->label,
                'code' => 'CODE-001',
                'employee_code' => 'EMP-00001',
                'employment_type' => 'Full time',
                'status' => 'Active',
                'role' => 'employee',
                default => 'Sample value',
            },
        };
    }
}
