<?php

namespace Tests\Support;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\ImportSession;
use App\Services\Import\ImportFieldDefinition;

/**
 * Test-only importable entity adapter. No production entity adapters ship in Phase 8.1.
 */
class FakeImportableEntity implements ImportableEntityInterface
{
    /** @var list<array{row: array<string, mixed>, session_id: int}> */
    public array $persisted = [];

    public function __construct(protected string $type = 'demo') {}

    public function entityType(): string
    {
        return $this->type;
    }

    public function entityLabel(): string
    {
        return 'Demo Entity';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(
                key: 'email',
                label: 'Email',
                required: true,
                dataType: ImportFieldDefinition::TYPE_EMAIL,
                aliases: ['Email Address', 'E-mail'],
            ),
            new ImportFieldDefinition(
                key: 'full_name',
                label: 'Full Name',
                required: true,
                dataType: ImportFieldDefinition::TYPE_STRING,
                aliases: ['Name', 'Contact Name'],
            ),
            new ImportFieldDefinition(
                key: 'phone',
                label: 'Phone',
                required: false,
                dataType: ImportFieldDefinition::TYPE_PHONE,
                aliases: ['Phone Number', 'Mobile'],
            ),
            new ImportFieldDefinition(
                key: 'amount',
                label: 'Amount',
                required: false,
                dataType: ImportFieldDefinition::TYPE_NUMBER,
            ),
            new ImportFieldDefinition(
                key: 'started_on',
                label: 'Started On',
                required: false,
                dataType: ImportFieldDefinition::TYPE_DATE,
                aliases: ['Start Date'],
            ),
            new ImportFieldDefinition(
                key: 'custom_segment',
                label: 'Segment',
                required: false,
                dataType: ImportFieldDefinition::TYPE_STRING,
                supportsMetadata: true,
            ),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        return [];
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $this->persisted[] = [
            'row' => $mappedRow,
            'session_id' => $session->id,
        ];

        return [
            'action' => 'created',
            'id' => count($this->persisted),
        ];
    }
}
