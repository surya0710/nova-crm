<?php

namespace Tests\Unit;

use App\Contracts\InterviewMeetingProviderInterface;
use App\Models\Organization;
use App\Models\RecruitmentProvider;
use App\Services\Recruitment\Providers\CustomMeetingUrlProvider;
use App\Services\Recruitment\Providers\GoogleMeetProvider;
use App\Services\Recruitment\Providers\JitsiMeetProvider;
use App\Services\Recruitment\Providers\MicrosoftTeamsMeetingProvider;
use App\Services\Recruitment\Providers\ZoomMeetingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InterviewMeetingProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_providers_implement_interface_and_generate_links(): void
    {
        $organization = Organization::factory()->create();
        $provider = new RecruitmentProvider([
            'organization_id' => $organization->id,
            'slug' => 'google_meet',
            'display_name' => 'Google Meet',
            'category' => 'meeting',
            'status' => RecruitmentProvider::STATUS_CONNECTED,
        ]);

        $adapters = [
            new GoogleMeetProvider,
            new MicrosoftTeamsMeetingProvider,
            new ZoomMeetingProvider,
            new JitsiMeetProvider,
        ];

        foreach ($adapters as $adapter) {
            $this->assertInstanceOf(InterviewMeetingProviderInterface::class, $adapter);
            $result = $adapter->generateMeeting($provider, [
                'title' => 'Interview',
                'starts_at' => now()->toIso8601String(),
            ]);
            $this->assertTrue($result['ok']);
            $this->assertNotEmpty($result['meeting_url']);
            $this->assertNotEmpty($result['meeting_id']);
        }
    }

    public function test_custom_meeting_url_requires_valid_url(): void
    {
        $this->expectException(ValidationException::class);

        (new CustomMeetingUrlProvider)->generateMeeting(new RecruitmentProvider([
            'status' => RecruitmentProvider::STATUS_CONNECTED,
        ]), [
            'title' => 'Interview',
            'starts_at' => now()->toIso8601String(),
            'custom_url' => 'not-a-url',
        ]);
    }

    public function test_recruitment_config_registers_meeting_drivers(): void
    {
        $catalog = config('recruitment.providers.catalog');
        foreach (['google_meet', 'microsoft_teams', 'zoom', 'jitsi_meet', 'custom_meeting_url'] as $slug) {
            $this->assertArrayHasKey($slug, $catalog);
            $this->assertSame('meeting', $catalog[$slug]['category']);
            $this->assertEmpty($catalog[$slug]['coming_soon'] ?? null);
        }
    }
}
