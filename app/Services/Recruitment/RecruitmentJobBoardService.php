<?php

namespace App\Services\Recruitment;

use App\Contracts\RecruitmentJobBoardProviderInterface;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Models\RecruitmentJobBoardListing;
use App\Models\RecruitmentProvider;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecruitmentJobBoardService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
        protected RecruitmentProviderService $providers,
        protected AuditLogger $auditLogger,
        protected NotificationService $notificationService,
    ) {}

    public function publishOpening(
        JobOpening $opening,
        RecruitmentProvider $provider,
        ?User $actor = null,
    ): RecruitmentJobBoardListing {
        if ($opening->status !== 'published') {
            throw ValidationException::withMessages([
                'status' => 'Only published openings may be published externally.',
            ]);
        }

        if (! $provider->isConnected()) {
            throw ValidationException::withMessages([
                'provider' => 'Job board provider must be connected.',
            ]);
        }

        $adapter = $this->registry->resolve($provider->slug);

        if (! $adapter instanceof RecruitmentJobBoardProviderInterface) {
            throw ValidationException::withMessages([
                'provider' => 'Provider does not support job board publishing.',
            ]);
        }

        $payload = $this->openingPayload($opening);

        return DB::transaction(function () use ($opening, $provider, $adapter, $payload, $actor) {
            $listing = RecruitmentJobBoardListing::query()->firstOrNew([
                'job_opening_id' => $opening->id,
                'recruitment_provider_id' => $provider->id,
            ]);
            $listing->organization_id = $opening->organization_id;
            $listing->payload = $payload;
            $listing->attempt_count = (int) $listing->attempt_count + 1;

            try {
                if ($listing->external_job_id && $listing->status !== 'closed') {
                    $result = $adapter->updateOpening($provider, $listing->external_job_id, $payload);
                    $status = 'updated';
                } else {
                    $result = $adapter->publishOpening($provider, $payload);
                    $status = 'published';
                }

                if (! ($result['ok'] ?? false)) {
                    throw new \RuntimeException($result['message'] ?? 'Job board publish failed.');
                }

                $listing->external_job_id = $result['external_job_id'] ?? $listing->external_job_id;
                $listing->status = $status;
                $listing->last_error = null;
                $listing->next_retry_at = null;
                $listing->published_at = $listing->published_at ?? now();
                $listing->closed_at = null;
                $listing->last_synced_at = now();
                $listing->metadata = $result['metadata'] ?? null;
                $listing->save();

                $this->auditLogger->log($listing, 'recruitment_job_board_published', [
                    'job_opening_id' => $opening->id,
                    'provider' => $provider->slug,
                    'external_job_id' => $listing->external_job_id,
                    'status' => $listing->status,
                ], $actor);

                $provider->last_synced_at = now();
                $provider->last_error = null;
                $provider->save();

                return $listing;
            } catch (Throwable $e) {
                $listing->status = 'failed';
                $listing->last_error = $e->getMessage();
                $listing->next_retry_at = $this->nextRetryAt((int) $listing->attempt_count);
                $listing->save();

                $this->notifyPublishFailure($opening->organization_id, $provider, $e->getMessage(), $actor);

                return $listing;
            }
        });
    }

    public function closeOpening(JobOpening $opening, ?User $actor = null): void
    {
        $listings = RecruitmentJobBoardListing::query()
            ->where('job_opening_id', $opening->id)
            ->whereIn('status', ['published', 'updated', 'pending', 'failed'])
            ->with('provider')
            ->get();

        foreach ($listings as $listing) {
            $this->closeListing($listing, $actor);
        }
    }

    public function closeListing(RecruitmentJobBoardListing $listing, ?User $actor = null): RecruitmentJobBoardListing
    {
        $provider = $listing->provider;

        if (! $provider || ! $listing->external_job_id || ! $this->registry->has($provider->slug)) {
            $listing->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            return $listing->fresh();
        }

        try {
            $adapter = $this->registry->resolve($provider->slug);
            if ($adapter instanceof RecruitmentJobBoardProviderInterface) {
                $result = $adapter->closeOpening($provider, (string) $listing->external_job_id);
                if (! ($result['ok'] ?? false)) {
                    throw new \RuntimeException($result['message'] ?? 'Close failed.');
                }
            }

            $listing->update([
                'status' => 'closed',
                'closed_at' => now(),
                'last_error' => null,
                'last_synced_at' => now(),
                'next_retry_at' => null,
            ]);

            $this->auditLogger->log($listing, 'recruitment_job_board_closed', [
                'job_opening_id' => $listing->job_opening_id,
                'provider' => $provider->slug,
                'external_job_id' => $listing->external_job_id,
            ], $actor);
        } catch (Throwable $e) {
            $listing->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'attempt_count' => (int) $listing->attempt_count + 1,
                'next_retry_at' => $this->nextRetryAt((int) $listing->attempt_count + 1),
            ]);
            $this->notifyPublishFailure($listing->organization_id, $provider, $e->getMessage(), $actor);
        }

        return $listing->fresh();
    }

    public function syncStatus(RecruitmentJobBoardListing $listing, ?User $actor = null): RecruitmentJobBoardListing
    {
        $provider = $listing->provider;
        if (! $provider || ! $listing->external_job_id) {
            return $listing;
        }

        $adapter = $this->registry->resolve($provider->slug);
        if (! $adapter instanceof RecruitmentJobBoardProviderInterface) {
            return $listing;
        }

        try {
            $result = $adapter->syncStatus($provider, (string) $listing->external_job_id);
            $listing->update([
                'metadata' => array_merge($listing->metadata ?? [], $result['metadata'] ?? []),
                'last_synced_at' => now(),
                'last_error' => ($result['ok'] ?? false) ? null : ($result['message'] ?? 'Sync failed'),
            ]);
            $this->auditLogger->log($listing, 'recruitment_job_board_status_synced', [
                'external_job_id' => $listing->external_job_id,
                'remote_status' => $result['status'] ?? null,
            ], $actor);
        } catch (Throwable $e) {
            $listing->update(['last_error' => $e->getMessage()]);
        }

        return $listing->fresh();
    }

    /**
     * Retry failed publishes using platform-style backoff.
     */
    public function processRetries(Organization $organization): int
    {
        $max = (int) config('recruitment.job_board.max_publish_attempts', 5);
        $listings = RecruitmentJobBoardListing::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'failed')
            ->where('attempt_count', '<', $max)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->with(['jobOpening', 'provider'])
            ->get();

        $processed = 0;
        foreach ($listings as $listing) {
            if (! $listing->jobOpening || ! $listing->provider) {
                continue;
            }
            if ($listing->jobOpening->status === 'closed') {
                $this->closeListing($listing);
            } else {
                $this->publishOpening($listing->jobOpening, $listing->provider);
            }
            $processed++;
        }

        return $processed;
    }

    /**
     * Safe auto-close when openings close — never throws.
     */
    public function tryCloseExternalListings(JobOpening $opening, ?User $actor = null): void
    {
        try {
            $this->closeOpening($opening, $actor);
        } catch (Throwable) {
            // Provider failures must not interrupt recruitment workflows.
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function openingPayload(JobOpening $opening): array
    {
        return [
            'id' => $opening->id,
            'title' => $opening->title,
            'description' => $opening->description,
            'location' => $opening->location,
            'status' => $opening->status,
            'employment_type' => $opening->employment_type,
            'salary_range_min' => $opening->salary_range_min,
            'salary_range_max' => $opening->salary_range_max,
            'publish_date' => $opening->publish_date?->toDateString(),
            'closing_date' => $opening->closing_date?->toDateString(),
        ];
    }

    protected function nextRetryAt(int $attempt): \Carbon\CarbonInterface
    {
        $backoff = config('recruitment.webhooks.retry_backoff_seconds', [60, 300, 900, 3600, 7200]);
        $seconds = $backoff[min(max($attempt - 1, 0), count($backoff) - 1)] ?? 3600;

        return now()->addSeconds((int) $seconds);
    }

    protected function notifyPublishFailure(int $organizationId, ?RecruitmentProvider $provider, string $message, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        try {
            $this->notificationService->send(
                $organizationId,
                $actor->id,
                'Job publishing failed',
                ($provider?->display_name ?? 'Job board').': '.$message,
                '/hrms/recruitment/integrations/job-boards',
            );
        } catch (Throwable) {
        }
    }
}
