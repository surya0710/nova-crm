<?php

namespace App\Jobs;

use App\Models\UserProvisioningBatch;
use App\Services\Identity\BulkEmployeeUserProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BulkProvisionEmployeeUsersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $batchId) {}

    public function handle(BulkEmployeeUserProvisioningService $service): void
    {
        $batch = UserProvisioningBatch::query()->withoutGlobalScopes()->find($this->batchId);
        if (! $batch) {
            return;
        }

        $service->process($batch);
    }
}
