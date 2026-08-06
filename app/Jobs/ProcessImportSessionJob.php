<?php

namespace App\Jobs;

use App\Models\ImportSession;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportSessionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $backoff = 60;

    public int $timeout = 3600;

    public function __construct(public int $sessionId, public ?int $actorId = null)
    {
        $this->onQueue('imports');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('import-session-'.$this->sessionId))
                ->dontRelease()
                ->expireAfter(3600),
        ];
    }

    public function handle(ImportPlatformService $imports, TenantContext $tenant): void
    {
        $session = ImportSession::query()->withoutGlobalScopes()->find($this->sessionId);
        if (! $session) {
            Log::error('import.job.session_missing', [
                'import_id' => $this->sessionId,
                'user_id' => $this->actorId,
            ]);

            return;
        }

        if ($session->status !== ImportSession::STATUS_QUEUED) {
            Log::warning('import.job.invalid_status', [
                'import_id' => $session->id,
                'organization_id' => $session->organization_id,
                'user_id' => $this->actorId,
                'status' => $session->status,
                'expected_status' => ImportSession::STATUS_QUEUED,
            ]);

            return;
        }

        $organization = $session->organization;
        if ($organization) {
            $tenant->set($organization);
        }

        $actor = $this->actorId ? User::query()->find($this->actorId) : null;
        $imports->executeImport($session, $actor);
    }

    public function failed(?Throwable $exception): void
    {
        $session = ImportSession::query()->withoutGlobalScopes()->find($this->sessionId);

        if ($session && in_array($session->status, [
            ImportSession::STATUS_QUEUED,
            ImportSession::STATUS_IMPORTING,
        ], true)) {
            $session->forceFill([
                'status' => ImportSession::STATUS_FAILED,
                'last_error' => $exception?->getMessage() ?? 'Import queue job failed.',
                'completed_at' => now(),
            ])->save();
        }

        Log::critical('import.job.failed', [
            'import_id' => $this->sessionId,
            'organization_id' => $session?->organization_id,
            'user_id' => $this->actorId,
            'exception_class' => $exception ? $exception::class : null,
            'exception' => $exception,
            'reason' => $exception?->getMessage() ?? 'Import queue job failed.',
        ]);
    }
}
