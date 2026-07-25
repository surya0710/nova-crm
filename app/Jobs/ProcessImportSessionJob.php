<?php

namespace App\Jobs;

use App\Models\ImportSession;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImportSessionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $sessionId, public ?int $actorId = null) {}

    public function handle(ImportPlatformService $imports, TenantContext $tenant): void
    {
        $session = ImportSession::query()->withoutGlobalScopes()->find($this->sessionId);
        if (! $session) {
            return;
        }

        if ($session->status !== ImportSession::STATUS_READY) {
            return;
        }

        $organization = $session->organization;
        if ($organization) {
            $tenant->set($organization);
        }

        $actor = $this->actorId ? User::query()->find($this->actorId) : null;
        $imports->executeImport($session, $actor);
    }
}
