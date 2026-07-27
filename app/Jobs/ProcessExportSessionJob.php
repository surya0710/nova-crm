<?php

namespace App\Jobs;

use App\Models\ExportSession;
use App\Services\Export\ExportPlatformService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessExportSessionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public int $sessionId) {}

    public function handle(ExportPlatformService $exports, TenantContext $tenant): void
    {
        $session = ExportSession::query()->withoutGlobalScopes()->find($this->sessionId);
        if (! $session || $session->isTerminal()) {
            return;
        }

        if ($organization = $session->organization) {
            $tenant->set($organization);
        }

        $exports->process($session);
    }
}
