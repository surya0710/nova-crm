<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Recruitment\RecruitmentIntegrationService;
use Illuminate\Console\Command;

class ProcessRecruitmentIntegrationRetries extends Command
{
    protected $signature = 'recruitment:process-integration-retries {--organization= : Limit to one organization ID}';

    protected $description = 'Retry failed recruitment job board publishes and outbound webhook deliveries';

    public function handle(RecruitmentIntegrationService $integrations): int
    {
        $organizationId = $this->option('organization');

        $query = Organization::query()->orderBy('id');
        if ($organizationId) {
            $query->whereKey((int) $organizationId);
        }

        $total = ['job_board' => 0, 'webhooks' => 0];

        foreach ($query->cursor() as $organization) {
            $result = $integrations->processRetries($organization);
            $total['job_board'] += $result['job_board'];
            $total['webhooks'] += $result['webhooks'];
        }

        $this->info("Processed {$total['job_board']} job board retries and {$total['webhooks']} webhook retries.");

        return self::SUCCESS;
    }
}
