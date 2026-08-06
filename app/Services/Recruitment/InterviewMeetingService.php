<?php

namespace App\Services\Recruitment;

use App\Contracts\InterviewMeetingProviderInterface;
use App\Models\InterviewRound;
use App\Models\Organization;
use App\Models\RecruitmentProvider;
use App\Services\Recruitment\Providers\CustomMeetingUrlProvider;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class InterviewMeetingService
{
    public function __construct(
        protected RecruitmentProviderRegistry $registry,
    ) {}

    /**
     * @param  array{
     *     meeting_provider?: string|null,
     *     meeting_link?: string|null,
     *     custom_url?: string|null,
     *     join_instructions?: string|null,
     *     scheduled_at?: mixed,
     *     duration_minutes?: int|null,
     *     title?: string|null,
     * }  $data
     * @return array{meeting_link: ?string, meeting_provider: ?string, meeting_id: ?string, join_instructions: ?string}
     */
    public function generateForRound(InterviewRound $round, Organization $organization, array $data): array
    {
        $providerSlug = $data['meeting_provider'] ?? null;

        if ($providerSlug === null || $providerSlug === '' || $providerSlug === 'none') {
            return [
                'meeting_link' => $data['meeting_link'] ?? null,
                'meeting_provider' => null,
                'meeting_id' => null,
                'join_instructions' => $data['join_instructions'] ?? null,
            ];
        }

        if (! $this->registry->has($providerSlug)) {
            throw ValidationException::withMessages([
                'meeting_provider' => __('The selected meeting provider is not available.'),
            ]);
        }

        $adapter = $this->registry->resolve($providerSlug);

        if (! $adapter instanceof InterviewMeetingProviderInterface) {
            throw ValidationException::withMessages([
                'meeting_provider' => __('The selected meeting provider is not available.'),
            ]);
        }

        $provider = $this->resolveProviderModel($organization, $providerSlug, $adapter);

        $startsAt = isset($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'])->toIso8601String()
            : ($round->scheduled_at?->toIso8601String() ?? now()->toIso8601String());

        $result = $adapter->generateMeeting($provider, [
            'title' => $data['title'] ?? __('Interview').' #'.$round->round_number,
            'starts_at' => $startsAt,
            'duration_minutes' => $data['duration_minutes'] ?? $round->duration_minutes,
            'custom_url' => $data['custom_url'] ?? $data['meeting_link'] ?? null,
            'join_instructions' => $data['join_instructions'] ?? null,
        ]);

        if (! ($result['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'meeting_provider' => $result['message'] ?? __('Unable to generate meeting link.'),
            ]);
        }

        return [
            'meeting_link' => $result['meeting_url'] ?? null,
            'meeting_provider' => $providerSlug,
            'meeting_id' => $result['meeting_id'] ?? null,
            'join_instructions' => $result['join_instructions'] ?? null,
        ];
    }

    public function cancelForRound(InterviewRound $round, Organization $organization): void
    {
        if (! $round->meeting_provider || ! $round->meeting_id) {
            return;
        }

        if (! $this->registry->has($round->meeting_provider)) {
            return;
        }

        $adapter = $this->registry->resolve($round->meeting_provider);
        if (! $adapter instanceof InterviewMeetingProviderInterface) {
            return;
        }

        $provider = RecruitmentProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $round->meeting_provider)
            ->first();

        if ($provider) {
            $adapter->cancelMeeting($provider, (string) $round->meeting_id);
        }
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public function availableProviders(): array
    {
        $catalog = config('recruitment.providers.catalog', []);
        $items = [];

        foreach ($catalog as $slug => $meta) {
            if (($meta['category'] ?? null) !== 'meeting') {
                continue;
            }
            if (! empty($meta['coming_soon'])) {
                continue;
            }
            $items[] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? $slug,
            ];
        }

        return $items;
    }

    protected function resolveProviderModel(
        Organization $organization,
        string $slug,
        InterviewMeetingProviderInterface $adapter,
    ): RecruitmentProvider {
        $provider = RecruitmentProvider::query()
            ->where('organization_id', $organization->id)
            ->where('slug', $slug)
            ->first();

        if ($provider) {
            return $provider;
        }

        // Stateless providers (Jitsi / custom URL) do not require a saved connection.
        if ($adapter instanceof CustomMeetingUrlProvider || $slug === 'jitsi_meet') {
            return new RecruitmentProvider([
                'organization_id' => $organization->id,
                'slug' => $slug,
                'display_name' => $adapter->displayName(),
                'category' => 'meeting',
                'status' => RecruitmentProvider::STATUS_CONNECTED,
            ]);
        }

        throw ValidationException::withMessages([
            'meeting_provider' => __('Connect :provider under Recruitment Integrations before scheduling.', [
                'provider' => $adapter->displayName(),
            ]),
        ]);
    }
}
