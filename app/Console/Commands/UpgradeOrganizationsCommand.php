<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Platform\OrganizationUpgradeService;
use Illuminate\Console\Command;

class UpgradeOrganizationsCommand extends Command
{
    protected $signature = 'organization:upgrade
        {--all : Upgrade every organization}
        {--organization= : Upgrade a single organization by ID}
        {--dry-run : Report what would change without writing}';

    protected $description = 'Provision missing module assignments and preferences for organizations (idempotent).';

    public function handle(OrganizationUpgradeService $upgrade): int
    {
        $all = (bool) $this->option('all');
        $organizationId = $this->option('organization');
        $dryRun = (bool) $this->option('dry-run');

        if (! $all && ($organizationId === null || $organizationId === '')) {
            $this->error('Provide --all or --organization={id}.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Dry run — no database writes will be performed.');
            $this->newLine();
        }

        $organizations = $all
            ? Organization::query()->orderBy('id')->get()
            : Organization::query()->whereKey((int) $organizationId)->get();

        if ($organizations->isEmpty()) {
            $this->error('No matching organizations found.');

            return self::FAILURE;
        }

        foreach ($organizations as $organization) {
            $this->line("Checking Organization: {$organization->name} (#{$organization->id})...");
            $this->newLine();

            $result = $upgrade->upgrade($organization, $dryRun);

            foreach ($result['modules_checked'] as $moduleKey) {
                $definition = config("modules.modules.{$moduleKey}");
                $label = is_array($definition) ? ($definition['name'] ?? $moduleKey) : $moduleKey;
                $added = in_array($moduleKey, $result['modules_added'], true);
                $prefix = $added ? ($dryRun ? '○ Would add' : '✔ Added') : '· Present';
                $this->line("  {$prefix}  {$label}");
            }

            $this->newLine();

            if ($result['modules_added'] !== []) {
                $this->info($dryRun
                    ? '○ Would add missing modules'
                    : '✔ Added missing modules');
            } else {
                $this->line('✔ Modules already provisioned');
            }

            $this->info($result['dashboard_preferences']
                ? ($dryRun ? '○ Dashboard preferences' : '✔ Dashboard preferences')
                : '· Dashboard preferences skipped');

            $this->info($result['notification_preferences'] > 0 || $dryRun
                ? ($dryRun ? '○ Notification preferences' : '✔ Notification preferences')
                : '✔ Notification preferences');

            $this->info($result['workspace_preferences'] || $dryRun
                ? ($dryRun ? '○ Workspace preferences' : '✔ Workspace preferences')
                : '✔ Workspace preferences');

            if ($result['users_upgraded'] > 0) {
                $this->info(($dryRun ? '○' : '✔')." User preferences ({$result['users_upgraded']})");
            }

            $this->newLine();
            $this->info('Done');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
