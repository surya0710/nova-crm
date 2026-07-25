<?php

namespace App\Services\Recruitment;

use App\Models\Organization;
use App\Models\RecruitmentJobBoardListing;
use App\Models\RecruitmentWebhookDelivery;
use App\Models\User;

/**
 * Facade coordinating recruitment external integrations.
 * Domain business rules remain in dedicated Recruitment services.
 */
class RecruitmentIntegrationService
{
    public function __construct(
        protected RecruitmentProviderService $providers,
        protected RecruitmentCalendarService $calendar,
        protected RecruitmentCommunicationService $communication,
        protected RecruitmentJobBoardService $jobBoards,
        protected ResumeParsingService $resumeParsing,
        protected BackgroundVerificationService $backgroundVerification,
        protected RecruitmentWebhookService $webhooks,
        protected RecruitmentApiService $api,
    ) {}

    public function providers(): RecruitmentProviderService
    {
        return $this->providers;
    }

    public function calendar(): RecruitmentCalendarService
    {
        return $this->calendar;
    }

    public function communication(): RecruitmentCommunicationService
    {
        return $this->communication;
    }

    public function jobBoards(): RecruitmentJobBoardService
    {
        return $this->jobBoards;
    }

    public function resumeParsing(): ResumeParsingService
    {
        return $this->resumeParsing;
    }

    public function backgroundVerification(): BackgroundVerificationService
    {
        return $this->backgroundVerification;
    }

    public function webhooks(): RecruitmentWebhookService
    {
        return $this->webhooks;
    }

    public function api(): RecruitmentApiService
    {
        return $this->api;
    }

    /**
     * Diagnostics payload for Recruitment Integrations UI / Provider Health Center.
     *
     * @return array<string, mixed>
     */
    public function diagnostics(Organization $organization): array
    {
        $cards = $this->providers->integrationCardsForOrganization($organization);
        $retryQueue = [
            'job_board_failures' => RecruitmentJobBoardListing::query()
                ->where('organization_id', $organization->id)
                ->where('status', 'failed')
                ->count(),
            'webhook_failures' => RecruitmentWebhookDelivery::query()
                ->where('organization_id', $organization->id)
                ->where('status', 'failed')
                ->count(),
        ];

        return [
            'generated_at' => now()->toIso8601String(),
            'organization_id' => $organization->id,
            'providers' => $cards,
            'retry_queue' => $retryQueue,
            'summary' => [
                'connected' => collect($cards)->where('connected', true)->count(),
                'total' => count($cards),
                'with_errors' => collect($cards)->filter(fn ($c) => filled($c['last_error']))->count(),
            ],
        ];
    }

    public function connectProvider(Organization $organization, string $slug, User $actor)
    {
        return $this->providers->connect($organization, $slug, $actor);
    }

    public function processRetries(Organization $organization): array
    {
        return [
            'job_board' => $this->jobBoards->processRetries($organization),
            'webhooks' => $this->webhooks->processRetries($organization),
        ];
    }
}
