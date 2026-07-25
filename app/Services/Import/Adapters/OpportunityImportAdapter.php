<?php

namespace App\Services\Import\Adapters;

use App\Contracts\Import\ImportableEntityInterface;
use App\Models\Customer;
use App\Models\ImportSession;
use App\Models\Opportunity;
use App\Services\Import\Adapters\Concerns\OrganizationImportSupport;
use App\Services\Import\ImportFieldDefinition;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class OpportunityImportAdapter implements ImportableEntityInterface
{
    use OrganizationImportSupport;

    public function __construct(protected TenantContext $tenant) {}

    public function entityType(): string
    {
        return 'opportunity';
    }

    public function entityLabel(): string
    {
        return 'Opportunity';
    }

    public function fieldDefinitions(): array
    {
        return [
            new ImportFieldDefinition(key: 'title', label: 'Title', required: true, aliases: ['Opportunity', 'Name', 'Deal Name']),
            new ImportFieldDefinition(key: 'customer_email', label: 'Customer Email', required: false, dataType: ImportFieldDefinition::TYPE_EMAIL, aliases: ['Customer', 'Account Email']),
            new ImportFieldDefinition(key: 'stage', label: 'Stage', required: false),
            new ImportFieldDefinition(key: 'amount', label: 'Amount', required: false, dataType: ImportFieldDefinition::TYPE_NUMBER, aliases: ['Value', 'Deal Value']),
            new ImportFieldDefinition(key: 'currency', label: 'Currency', required: false),
            new ImportFieldDefinition(key: 'probability', label: 'Probability', required: false, dataType: ImportFieldDefinition::TYPE_INTEGER),
            new ImportFieldDefinition(key: 'expected_close_date', label: 'Expected Close Date', required: false, dataType: ImportFieldDefinition::TYPE_DATE, aliases: ['Close Date']),
            new ImportFieldDefinition(key: 'description', label: 'Description', required: false, aliases: ['Notes']),
        ];
    }

    public function validateMappedRows(array $rows, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $errors = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! ($row['valid'] ?? true)) {
                continue;
            }
            $values = $row['values'];
            $rowNumber = (int) $row['row_number'];
            $title = $this->stringOrNull($values['title'] ?? null);
            $customerEmail = $this->normalizeEmail($values['customer_email'] ?? null);
            $key = strtolower((string) $title).'|'.($customerEmail ?? '');

            if (isset($seen[$key])) {
                $errors[] = $this->error($rowNumber, 'title', 'Duplicate opportunity in file.', $title);
            }
            $seen[$key] = true;

            if ($customerEmail) {
                $exists = Customer::query()
                    ->where('organization_id', $organization->id)
                    ->where('email', $customerEmail)
                    ->exists();
                if (! $exists) {
                    $errors[] = $this->error($rowNumber, 'customer_email', 'Customer email was not found.', $customerEmail);
                }
            }

            if ($this->shouldReportDatabaseDuplicates($session) && $title) {
                $dup = Opportunity::query()
                    ->where('organization_id', $organization->id)
                    ->where('title', $title)
                    ->exists();
                if ($dup) {
                    $errors[] = $this->error($rowNumber, 'title', 'Duplicate opportunity already exists.', $title);
                }
            }
        }

        return $errors;
    }

    public function persistRow(array $mappedRow, ImportSession $session): array
    {
        $organization = $this->organizationFor($session);
        $actor = $this->actorFor($session);
        $strategy = $this->duplicateStrategy($session);
        $title = $this->stringOrNull($mappedRow['title'] ?? null);
        $customerEmail = $this->normalizeEmail($mappedRow['customer_email'] ?? null);

        $customerId = null;
        if ($customerEmail) {
            $customerId = Customer::query()
                ->where('organization_id', $organization->id)
                ->where('email', $customerEmail)
                ->value('id');
        }

        $existing = Opportunity::query()
            ->where('organization_id', $organization->id)
            ->where('title', $title)
            ->first();

        if ($existing && $strategy === 'skip') {
            return ['action' => 'skipped', 'id' => $existing->id];
        }

        $payload = [
            'organization_id' => $organization->id,
            'title' => $title,
            'customer_id' => $customerId,
            'stage' => $this->stringOrNull($mappedRow['stage'] ?? null) ?? 'qualification',
            'amount' => $mappedRow['amount'] ?? null,
            'currency' => $this->stringOrNull($mappedRow['currency'] ?? null) ?? $organization->currency,
            'probability' => $this->parseInteger($mappedRow['probability'] ?? null),
            'expected_close_date' => $this->parseDate($mappedRow['expected_close_date'] ?? null)?->toDateString(),
            'description' => $this->stringOrNull($mappedRow['description'] ?? null),
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ];

        return DB::transaction(function () use ($existing, $strategy, $payload) {
            if ($existing && $strategy === 'update') {
                $existing->update($payload);

                return ['action' => 'updated', 'id' => $existing->id];
            }

            $opportunity = Opportunity::query()->create($payload);

            return ['action' => 'created', 'id' => $opportunity->id];
        });
    }
}
