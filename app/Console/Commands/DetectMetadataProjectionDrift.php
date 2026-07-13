<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Services\MetadataProjectionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DetectMetadataProjectionDrift extends Command
{
    protected $signature = 'metadata:projections:detect-drift
        {--organization_id= : Organization to inspect}
        {--entity_type= : Metadata-enabled entity type to inspect}
        {--entity_id= : Inspect one entity record}
        {--repair : Repair drift by rebuilding affected entity projections}
        {--chunk=500 : Number of records to process per chunk}';

    protected $description = 'Detect drift between canonical entity metadata JSON and derived projection rows.';

    public function handle(MetadataProjectionService $projection): int
    {
        $organizationId = $this->option('organization_id');
        $entityType = $this->option('entity_type');

        if (! $organizationId || ! $entityType) {
            $this->error('The --organization_id and --entity_type options are required.');

            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $entityId = $this->option('entity_id');
        $repair = (bool) $this->option('repair');
        $summary = [
            'inspected' => 0,
            'drifted' => 0,
            'repaired' => 0,
        ];

        $inspect = function (Model $record) use ($projection, $repair, &$summary) {
            $summary['inspected']++;
            $drift = $projection->detectDrift($record);

            if (! $drift['drifted']) {
                return;
            }

            $summary['drifted']++;
            $this->warn(sprintf(
                'Drift detected for %s:%s (missing=%d stale=%d extra=%d).',
                $record::class,
                $record->getKey(),
                count($drift['missing']),
                count($drift['stale']),
                count($drift['extra'])
            ));

            if ($repair) {
                $projection->repairDrift($record);
                $summary['repaired']++;
            }
        };

        if ($entityId) {
            $inspect($this->findRecord((int) $organizationId, (string) $entityType, (int) $entityId));
        } else {
            $this->entityQuery((int) $organizationId, (string) $entityType)
                ->chunkById($chunkSize, function ($records) use ($inspect) {
                    foreach ($records as $record) {
                        $inspect($record);
                    }
                });
        }

        $this->info("Inspected {$summary['inspected']} records; drifted {$summary['drifted']}; repaired {$summary['repaired']}.");

        return $summary['drifted'] > 0 && ! $repair ? self::FAILURE : self::SUCCESS;
    }

    protected function findRecord(int $organizationId, string $entityType, int $entityId): Model
    {
        return $this->entityQuery($organizationId, $entityType)->findOrFail($entityId);
    }

    protected function entityQuery(int $organizationId, string $entityType): Builder
    {
        return match ($entityType) {
            'lead' => Lead::withoutGlobalScopes()->where('organization_id', $organizationId),
            'customer' => Customer::withoutGlobalScopes()->where('organization_id', $organizationId),
            'opportunity' => Opportunity::withoutGlobalScopes()->where('organization_id', $organizationId),
            'organization' => Organization::withoutGlobalScopes()->whereKey($organizationId),
            default => throw new \InvalidArgumentException("Unsupported metadata projection entity type [{$entityType}]."),
        };
    }
}
