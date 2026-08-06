<?php

namespace App\Console\Commands;

use App\Services\MarketingBackfillService;
use Illuminate\Console\Command;
use Throwable;

class BackfillMarketingAttribution extends Command
{
    protected $signature = 'marketing:backfill-attribution
        {--organization= : Organization ID to backfill}
        {--lead= : Backfill a single lead ID}
        {--customer= : Backfill a single customer ID}
        {--opportunity= : Backfill a single opportunity ID}
        {--dry-run : Report what would happen without writing}
        {--chunk= : Records processed per chunk (default from config)}
        {--force : Reset resumable cursors and replay missing conversions for already-attributed entities}';

    protected $description = 'Backfill historical marketing attribution and missing conversion events.';

    public function handle(MarketingBackfillService $backfill): int
    {
        $organizationId = $this->option('organization');
        $leadId = $this->option('lead');
        $customerId = $this->option('customer');
        $opportunityId = $this->option('opportunity');

        if (! $organizationId && ! $leadId && ! $customerId && ! $opportunityId) {
            $this->error('Provide --organization= and/or one of --lead=, --customer=, --opportunity=.');

            return self::FAILURE;
        }

        $options = [
            'organization_id' => $organizationId !== null && $organizationId !== '' ? (int) $organizationId : null,
            'lead_id' => $leadId !== null && $leadId !== '' ? (int) $leadId : null,
            'customer_id' => $customerId !== null && $customerId !== '' ? (int) $customerId : null,
            'opportunity_id' => $opportunityId !== null && $opportunityId !== '' ? (int) $opportunityId : null,
            'dry_run' => (bool) $this->option('dry-run'),
            'force' => (bool) $this->option('force'),
            'chunk' => $this->option('chunk') !== null && $this->option('chunk') !== ''
                ? (int) $this->option('chunk')
                : (int) config('marketing.backfill.chunk_size', 100),
        ];

        if ($options['dry_run']) {
            $this->warn('Dry run — no database writes will be performed.');
        }

        try {
            $stats = $backfill->run($options);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Marketing attribution backfill complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['processed', $stats['processed']],
                ['skipped', $stats['skipped']],
                ['attributed', $stats['attributed']],
                ['conversions_replayed', $stats['conversions_replayed']],
                ['failed', $stats['failed']],
                ['would_attribute', $stats['would_attribute']],
                ['would_replay', $stats['would_replay']],
            ],
        );

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
