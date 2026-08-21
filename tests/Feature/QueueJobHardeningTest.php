<?php

namespace Tests\Feature;

use App\Jobs\BulkProvisionEmployeeUsersJob;
use App\Jobs\ProcessBulkOperationJob;
use App\Jobs\ProcessExportSessionJob;
use App\Jobs\ProcessImportSessionJob;
use App\Jobs\SendCrmEmailJob;
use App\Jobs\SendPayslipEmailJob;
use App\Listeners\RunTriggeredWorkflows;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QueueJobHardeningTest extends TestCase
{
    public function test_domain_jobs_have_explicit_queue_runtime_and_overlap_policies(): void
    {
        $jobs = [
            [new ProcessImportSessionJob(1), 'imports', 1, 3600],
            [new ProcessExportSessionJob(1), 'exports', 3, 1800],
            [new ProcessBulkOperationJob(1), 'bulk', 1, 1800],
            [new BulkProvisionEmployeeUsersJob(1), 'provisioning', 1, 1800],
            [new SendPayslipEmailJob(1), 'mail', 3, 120],
            [new SendCrmEmailJob(1), 'mail', 3, 120],
        ];

        foreach ($jobs as [$job, $queue, $tries, $timeout]) {
            $this->assertSame($queue, $job->queue);
            $this->assertSame($tries, $job->tries);
            $this->assertSame($timeout, $job->timeout);
            $this->assertNotEmpty($job->backoff);
            $this->assertContainsOnlyInstancesOf(WithoutOverlapping::class, $job->middleware());
        }
    }

    public function test_workflow_listener_and_maintenance_commands_are_registered(): void
    {
        $listener = app(RunTriggeredWorkflows::class);

        $this->assertSame('workflows', $listener->queue);
        $this->assertGreaterThan(0, $listener->tries);
        $this->assertGreaterThan(0, $listener->timeout);
        $this->assertNotEmpty($listener->backoff);

        $commands = Artisan::all();
        $this->assertArrayHasKey('operations:fail-stale-queue-work', $commands);
        $this->assertArrayHasKey('operations:reconcile-queue-state', $commands);
        $this->assertArrayHasKey('marketing:process-meta-webhooks', $commands);
    }
}
