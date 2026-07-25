<?php

namespace Tests\Unit\Recruitment;

use App\Contracts\BackgroundVerificationProviderInterface;
use App\Contracts\RecruitmentCalendarProviderInterface;
use App\Contracts\RecruitmentJobBoardProviderInterface;
use App\Contracts\ResumeParsingProviderInterface;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\Providers\RecruitmentProviderRegistry;
use App\Services\Recruitment\RecruitmentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentProviderAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_resolves_all_drivers_from_config(): void
    {
        $registry = app(RecruitmentProviderRegistry::class);
        $driverClasses = config('recruitment.providers.drivers', []);

        $this->assertNotEmpty($driverClasses);

        foreach ($driverClasses as $class) {
            $adapter = app($class);
            $this->assertTrue($registry->has($adapter->slug()), "Missing driver for [{$adapter->slug()}]");
            $resolved = $registry->resolve($adapter->slug());
            $this->assertInstanceOf($class, $resolved);
        }

        $this->assertCount(count($driverClasses), $registry->slugs());
    }

    public function test_each_adapter_implements_slug_category_capabilities(): void
    {
        $registry = app(RecruitmentProviderRegistry::class);

        foreach ($registry->all() as $slug => $adapter) {
            $this->assertSame($slug, $adapter->slug());
            $this->assertNotEmpty($adapter->displayName());
            $this->assertNotEmpty($adapter->category());
            $this->assertIsArray($adapter->capabilities());
            $this->assertNotEmpty($adapter->capabilities());
        }
    }

    public function test_calendar_adapters_create_event(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $providers = app(RecruitmentProviderService::class);
        $registry = app(RecruitmentProviderRegistry::class);

        foreach ($registry->all() as $adapter) {
            if (! $adapter instanceof RecruitmentCalendarProviderInterface) {
                continue;
            }

            $provider = $providers->connect($organization, $adapter->slug(), $user);
            $result = $adapter->createEvent($provider, [
                'title' => 'Interview',
                'starts_at' => now()->addDay()->toIso8601String(),
                'attendees' => [['email' => 'a@example.com', 'name' => 'A']],
            ]);

            $this->assertTrue($result['ok'] ?? false, $adapter->slug());
            $this->assertNotEmpty($result['external_event_id'] ?? null, $adapter->slug());
        }
    }

    public function test_job_board_adapters_publish_opening(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $providers = app(RecruitmentProviderService::class);
        $registry = app(RecruitmentProviderRegistry::class);

        foreach ($registry->all() as $adapter) {
            if (! $adapter instanceof RecruitmentJobBoardProviderInterface) {
                continue;
            }

            $provider = $providers->connect($organization, $adapter->slug(), $user);
            $result = $adapter->publishOpening($provider, [
                'id' => 99,
                'title' => 'Engineer',
                'status' => 'published',
                'description' => 'Build things',
            ]);

            $this->assertTrue($result['ok'] ?? false, $adapter->slug());
            $this->assertNotEmpty($result['external_job_id'] ?? null, $adapter->slug());
        }
    }

    public function test_resume_parser_parse_resume(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $providers = app(RecruitmentProviderService::class);
        $registry = app(RecruitmentProviderRegistry::class);

        foreach ($registry->all() as $adapter) {
            if (! $adapter instanceof ResumeParsingProviderInterface) {
                continue;
            }

            $provider = $providers->connect($organization, $adapter->slug(), $user);
            $result = $adapter->parseResume($provider, [
                'filename' => 'cv.pdf',
                'mime_type' => 'application/pdf',
            ]);

            $this->assertTrue($result['ok'] ?? false, $adapter->slug());
            $this->assertArrayHasKey('contact', $result);
        }
    }

    public function test_bgv_submit_verification(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $providers = app(RecruitmentProviderService::class);
        $registry = app(RecruitmentProviderRegistry::class);

        foreach ($registry->all() as $adapter) {
            if (! $adapter instanceof BackgroundVerificationProviderInterface) {
                continue;
            }

            $provider = $providers->connect($organization, $adapter->slug(), $user);
            $result = $adapter->submitVerification($provider, [
                'candidate_id' => 1,
                'hiring_decision_id' => 1,
                'candidate_name' => 'Test Candidate',
            ]);

            $this->assertTrue($result['ok'] ?? false, $adapter->slug());
            $this->assertNotEmpty($result['external_verification_id'] ?? null, $adapter->slug());
        }
    }
}
