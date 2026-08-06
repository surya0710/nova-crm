<?php

namespace App\Jobs;

use App\Models\BulkOperation;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessBulkOperationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $backoff = 60;

    public int $timeout = 1800;

    public function __construct(public int $operationId)
    {
        $this->onQueue('bulk');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('bulk-operation-'.$this->operationId))
                ->dontRelease()
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(BulkOperationsService $bulk, TenantContext $tenant): void
    {
        $operation = BulkOperation::query()->withoutGlobalScopes()->find($this->operationId);
        if (! $operation || $operation->isTerminal()) {
            return;
        }

        if ($organization = $operation->organization) {
            $tenant->set($organization);
        }

        $bulk->process($operation);
    }

    public function failed(?Throwable $exception): void
    {
        BulkOperation::query()
            ->withoutGlobalScopes()
            ->whereKey($this->operationId)
            ->whereIn('status', [BulkOperation::STATUS_PENDING, BulkOperation::STATUS_QUEUED, BulkOperation::STATUS_RUNNING])
            ->update([
                'status' => BulkOperation::STATUS_FAILED,
                'last_error' => $exception?->getMessage() ?? 'Bulk operation queue job failed.',
                'completed_at' => now(),
            ]);
    }
}
