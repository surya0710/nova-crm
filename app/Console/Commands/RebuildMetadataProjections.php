<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Services\MetadataProjectionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class RebuildMetadataProjections extends Command
{
    protected $signature = 'metadata:projections:rebuild
        {--organization_id= : Restrict rebuild to one organization}
        {--entity_type= : Restrict rebuild to one metadata-enabled entity type}
        {--entity_id= : Rebuild one entity record}
        {--field_id= : Rebuild one metadata field definition}
        {--chunk=500 : Number of records to process per chunk}';

    protected $description = 'Rebuild derived metadata value projection rows from canonical entity JSON.';

    public function handle(MetadataProjectionService $projection): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $fieldId = $this->option('field_id');

        if ($fieldId) {
            $definition = MetadataFieldDefinition::withoutGlobalScopes()->findOrFail($fieldId);
            $summary = $projection->rebuildForField($definition, $chunkSize);

            $this->info("Rebuilt field [{$definition->key}] for {$summary['entities']} entities; projected {$summary['projected']} rows.");

            return self::SUCCESS;
        }

        $organizationId = $this->option('organization_id');
        $entityType = $this->option('entity_type');
        $entityId = $this->option('entity_id');

        if (! $organizationId || ! $entityType) {
            $this->error('The --organization_id and --entity_type options are required unless --field_id is provided.');

            return self::FAILURE;
        }

        if ($entityId) {
            $record = $this->findRecord((int) $organizationId, (string) $entityType, (int) $entityId);
            $summary = $projection->sync($record);

            $this->info("Rebuilt entity [{$entityType}:{$entityId}]; deleted {$summary['deleted']} rows and projected {$summary['projected']} rows.");

            return self::SUCCESS;
        }

        $summary = $projection->rebuildForOrganizationEntity((int) $organizationId, (string) $entityType, $chunkSize);

        $this->info("Rebuilt {$summary['entities']} [{$entityType}] entities; projected {$summary['projected']} rows.");

        return self::SUCCESS;
    }

    protected function findRecord(int $organizationId, string $entityType, int $entityId): Model
    {
        return match ($entityType) {
            'lead' => Lead::withoutGlobalScopes()->where('organization_id', $organizationId)->findOrFail($entityId),
            'customer' => Customer::withoutGlobalScopes()->where('organization_id', $organizationId)->findOrFail($entityId),
            'opportunity' => Opportunity::withoutGlobalScopes()->where('organization_id', $organizationId)->findOrFail($entityId),
            'organization' => Organization::withoutGlobalScopes()->whereKey($organizationId)->findOrFail($entityId),
            default => throw new \InvalidArgumentException("Unsupported metadata projection entity type [{$entityType}]."),
        };
    }
}
