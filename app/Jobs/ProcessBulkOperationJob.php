<?php

namespace App\Jobs;

use App\Models\BulkOperation;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBulkOperationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $operationId) {}

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
}
