<?php

namespace App\Jobs;

use App\Models\ExportSession;
use App\Services\Export\ExportPlatformService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessExportSessionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public int $timeout = 1800;

    public function __construct(public int $sessionId)
    {
        $this->onQueue('exports');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('export-session-'.$this->sessionId))
                ->releaseAfter(30)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(ExportPlatformService $exports, TenantContext $tenant): void
    {
        $session = ExportSession::query()->withoutGlobalScopes()->find($this->sessionId);
        if (! $session || $session->isTerminal()) {
            return;
        }

        if ($organization = $session->organization) {
            $tenant->set($organization);
        }

        $exports->process($session, finalizeFailure: false);
    }

    public function failed(?Throwable $exception): void
    {
        ExportSession::query()
            ->withoutGlobalScopes()
            ->whereKey($this->sessionId)
            ->whereIn('status', [ExportSession::STATUS_QUEUED, ExportSession::STATUS_RUNNING])
            ->update([
                'status' => ExportSession::STATUS_FAILED,
                'last_error' => $exception?->getMessage() ?? 'Export queue job failed.',
                'completed_at' => now(),
            ]);
    }
}
