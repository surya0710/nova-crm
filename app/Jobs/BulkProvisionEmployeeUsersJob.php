<?php

namespace App\Jobs;

use App\Models\UserProvisioningBatch;
use App\Services\Identity\BulkEmployeeUserProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class BulkProvisionEmployeeUsersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $backoff = 60;

    public int $timeout = 1800;

    public function __construct(public int $batchId)
    {
        $this->onQueue('provisioning');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('employee-provisioning-'.$this->batchId))
                ->dontRelease()
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(BulkEmployeeUserProvisioningService $service): void
    {
        $batch = UserProvisioningBatch::query()->withoutGlobalScopes()->find($this->batchId);
        if (! $batch) {
            return;
        }

        $service->process($batch);
    }

    public function failed(?Throwable $exception): void
    {
        $batch = UserProvisioningBatch::query()
            ->withoutGlobalScopes()
            ->whereKey($this->batchId)
            ->whereNotIn('status', ['completed', 'failed'])
            ->first();

        if (! $batch) {
            return;
        }

        $errors = $batch->errors ?? [];
        $errors[] = [
            'employee_id' => null,
            'error' => $exception?->getMessage() ?? 'Employee provisioning queue job failed.',
        ];
        $batch->update([
            'status' => 'failed',
            'finished_at' => now(),
            'errors' => $errors,
        ]);
    }
}
