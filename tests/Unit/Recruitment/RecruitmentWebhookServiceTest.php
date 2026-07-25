<?php

namespace Tests\Unit\Recruitment;

use App\Models\Organization;
use App\Models\RecruitmentWebhookDelivery;
use App\Models\User;
use App\Services\Recruitment\RecruitmentWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecruitmentWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_endpoint_and_dispatch_event_creates_delivery(): void
    {
        Http::fake([
            'https://hooks.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(RecruitmentWebhookService::class);

        $endpoint = $service->createEndpoint($organization, [
            'name' => 'ATS Hook',
            'url' => 'https://hooks.example.com/recruitment',
            'secret' => 'test-secret',
            'events' => ['application_submitted'],
            'is_active' => true,
        ], $user);

        $this->assertTrue($endpoint->is_active);
        $this->assertSame(['application_submitted'], $endpoint->events);

        $count = $service->dispatchEvent($organization, 'application_submitted', [
            'application_id' => 42,
        ]);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('recruitment_webhook_deliveries', [
            'organization_id' => $organization->id,
            'recruitment_webhook_endpoint_id' => $endpoint->id,
            'event_key' => 'application_submitted',
            'status' => 'delivered',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.example.com/recruitment'
                && $request->hasHeader('X-NovaCRM-Event', 'application_submitted');
        });
    }

    public function test_http_fake_successful_delivery(): void
    {
        Http::fake([
            'https://hooks.example.com/ok' => Http::response('accepted', 200),
        ]);

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(RecruitmentWebhookService::class);

        $endpoint = $service->createEndpoint($organization, [
            'name' => 'Success Hook',
            'url' => 'https://hooks.example.com/ok',
            'events' => ['offer_sent'],
            'is_active' => true,
        ], $user);

        $service->dispatchEvent($organization, 'offer_sent', ['offer_id' => 7]);

        $delivery = RecruitmentWebhookDelivery::query()
            ->where('recruitment_webhook_endpoint_id', $endpoint->id)
            ->firstOrFail();

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(200, $delivery->http_status);
        $this->assertNotNull($delivery->delivered_at);
        Http::assertSentCount(1);
    }

    public function test_process_retries_retries_failed(): void
    {
        Http::fake([
            'https://hooks.example.com/retry' => Http::sequence()
                ->push('fail', 500)
                ->push('ok', 200),
        ]);

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(RecruitmentWebhookService::class);

        $endpoint = $service->createEndpoint($organization, [
            'name' => 'Retry Hook',
            'url' => 'https://hooks.example.com/retry',
            'events' => ['interview_scheduled'],
            'is_active' => true,
        ], $user);

        $service->dispatchEvent($organization, 'interview_scheduled', ['round_id' => 1]);

        $delivery = RecruitmentWebhookDelivery::query()
            ->where('recruitment_webhook_endpoint_id', $endpoint->id)
            ->firstOrFail();

        $this->assertSame('failed', $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);

        $delivery->update(['next_retry_at' => now()->subMinute()]);

        $processed = $service->processRetries($organization);

        $this->assertSame(1, $processed);
        $this->assertSame('delivered', $delivery->fresh()->status);
        $this->assertSame(2, $delivery->fresh()->attempt_count);
        Http::assertSentCount(2);
    }
}
