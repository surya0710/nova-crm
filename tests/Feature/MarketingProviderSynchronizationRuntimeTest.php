<?php

namespace Tests\Feature;

use App\Models\MarketingProvider;
use App\Models\MarketingProviderSyncRun;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\Marketing\Providers\MarketingProviderRegistry;
use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Tests\Support\FakeMarketingProvider;
use Tests\TestCase;

class MarketingProviderSynchronizationRuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected MarketingProviderService $providers;

    protected MarketingProviderRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providers = app(MarketingProviderService::class);
        $this->registry = app(MarketingProviderRegistry::class);
    }

    /**
     * @return array{User, Organization, MarketingProvider, FakeMarketingProvider}
     */
    protected function connectedProvider(string $slug = 'sync_fake'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        $adapter = new FakeMarketingProvider($slug);
        $this->registry->register($adapter);
        $provider = $this->providers->registerProvider($organization, $slug);
        $this->providers->storeCredentials($provider, [
            'access_token' => 'sync-token',
            'expires_at' => now()->addHour(),
        ]);

        return [$user, $organization, $provider->fresh(), $adapter];
    }

    public function test_runtime_exposes_canonical_types_directions_and_statuses(): void
    {
        $this->assertSame([
            'lead_import',
            'webhook_processing',
            'asset_discovery',
            'form_sync',
            'conversion_upload',
        ], MarketingProviderSyncRun::SYNC_TYPES);
        $this->assertSame(['inbound', 'outbound'], MarketingProviderSyncRun::DIRECTIONS);
        $this->assertSame([
            'pending',
            'running',
            'completed',
            'partial',
            'failed',
            'cancelled',
        ], MarketingProviderSyncRun::STATUSES);
        $this->assertSame(MarketingProviderSyncRun::SYNC_TYPES, config('marketing.providers.synchronization.types'));
        $this->assertSame(MarketingProviderSyncRun::DIRECTIONS, config('marketing.providers.synchronization.directions'));
        $this->assertSame(MarketingProviderSyncRun::STATUSES, config('marketing.providers.synchronization.statuses'));
    }

    public function test_service_starts_updates_and_completes_a_run(): void
    {
        [, $organization, $provider] = $this->connectedProvider();

        $run = $this->providers->startSynchronization(
            $provider,
            MarketingProviderSyncRun::TYPE_LEAD_IMPORT,
            MarketingProviderSyncRun::DIRECTION_INBOUND,
            ['trigger' => 'manual'],
        );

        $this->assertSame($organization->id, $run->organization_id);
        $this->assertSame(MarketingProviderSyncRun::STATUS_RUNNING, $run->status);
        $this->assertNotNull($run->started_at);
        $this->assertNull($run->finished_at);

        $run = $this->providers->updateSynchronizationProgress(
            $run,
            processed: 5,
            succeeded: 4,
            failed: 1,
            message: 'Working',
            metadata: ['cursor' => 'next'],
        );
        $run = $this->providers->finishSynchronization(
            $run,
            MarketingProviderSyncRun::STATUS_PARTIAL,
            'Completed with one failure',
        );

        $this->assertSame(5, $run->records_processed);
        $this->assertSame(4, $run->records_succeeded);
        $this->assertSame(1, $run->records_failed);
        $this->assertSame('manual', $run->metadata['trigger']);
        $this->assertSame('next', $run->metadata['cursor']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_PARTIAL, $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->durationInSeconds());
    }

    public function test_progress_is_monotonic_and_finished_runs_cannot_change(): void
    {
        [, , $provider] = $this->connectedProvider();
        $run = $this->providers->startSynchronization(
            $provider,
            MarketingProviderSyncRun::TYPE_FORM_SYNC,
            MarketingProviderSyncRun::DIRECTION_INBOUND,
        );
        $run = $this->providers->updateSynchronizationProgress($run, 2, 2, 0);

        try {
            $this->providers->updateSynchronizationProgress($run, 1, 1, 0);
            $this->fail('Decreasing progress should fail.');
        } catch (InvalidArgumentException) {
            $this->assertSame(2, $run->fresh()->records_processed);
        }

        $run = $this->providers->finishSynchronization(
            $run,
            MarketingProviderSyncRun::STATUS_COMPLETED,
        );

        $this->expectException(LogicException::class);
        $this->providers->updateSynchronizationProgress($run, 3, 3, 0);
    }

    public function test_runtime_executes_adapter_and_persists_completed_history(): void
    {
        [, , $provider, $adapter] = $this->connectedProvider();
        $adapter->syncResult = [
            'ok' => true,
            'records_processed' => 3,
            'records_succeeded' => 3,
            'records_failed' => 0,
            'message' => 'Synchronized',
            'metadata' => ['provider_cursor' => 'abc'],
        ];

        $result = $this->providers->synchronize($provider, [
            'sync_type' => MarketingProviderSyncRun::TYPE_ASSET_DISCOVERY,
            'direction' => MarketingProviderSyncRun::DIRECTION_INBOUND,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_COMPLETED, $result['sync_status']);
        $this->assertSame(1, $adapter->syncCalls);
        $this->assertDatabaseHas('marketing_provider_sync_runs', [
            'id' => $result['sync_run_id'],
            'marketing_provider_id' => $provider->id,
            'status' => MarketingProviderSyncRun::STATUS_COMPLETED,
            'records_processed' => 3,
            'records_succeeded' => 3,
            'records_failed' => 0,
        ]);
        $this->assertNotNull($provider->fresh()->last_synced_at);
        $this->assertCount(1, $this->providers->synchronizationHistory($provider));
    }

    public function test_partial_provider_result_is_recorded_without_losing_history(): void
    {
        [, , $provider, $adapter] = $this->connectedProvider();
        $adapter->syncResult = [
            'ok' => true,
            'records_processed' => 4,
            'records_succeeded' => 3,
            'records_failed' => 1,
            'message' => 'One record rejected',
        ];

        $result = $this->providers->synchronize($provider, [
            'sync_type' => MarketingProviderSyncRun::TYPE_FORM_SYNC,
        ]);

        $this->assertSame(MarketingProviderSyncRun::STATUS_PARTIAL, $result['sync_status']);
        $this->assertSame(MarketingProvider::STATUS_CONNECTED, $provider->fresh()->status);
        $this->assertSame(1, $result['sync_run']->records_failed);
        $this->assertSame('One record rejected', $result['sync_run']->message);
    }

    public function test_provider_failure_and_unexpected_exception_finalize_runs(): void
    {
        [, $organization, $provider, $adapter] = $this->connectedProvider();
        $adapter->syncOk = false;

        $failed = $this->providers->synchronize($provider, [
            'sync_type' => MarketingProviderSyncRun::TYPE_ASSET_DISCOVERY,
        ]);

        $this->assertFalse($failed['ok']);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $failed['sync_status']);
        $this->assertNotNull($failed['sync_run']->finished_at);

        $this->providers->markConnected($provider->fresh());
        $adapter->throwDuringSync = true;

        try {
            $this->providers->synchronize($provider->fresh(), [
                'sync_type' => MarketingProviderSyncRun::TYPE_WEBHOOK_PROCESSING,
            ]);
            $this->fail('The adapter exception should be rethrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('unexpected sync failure', $e->getMessage());
        }

        $runs = MarketingProviderSyncRun::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $runs);
        $this->assertSame(MarketingProviderSyncRun::STATUS_FAILED, $runs->last()->status);
        $this->assertSame(RuntimeException::class, $runs->last()->metadata['exception']);
        $this->assertNotNull($runs->last()->finished_at);
    }

    public function test_running_synchronization_can_be_cancelled(): void
    {
        [, , $provider] = $this->connectedProvider();
        $run = $this->providers->startSynchronization(
            $provider,
            MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD,
            MarketingProviderSyncRun::DIRECTION_OUTBOUND,
        );

        $cancelled = $this->providers->cancelSynchronization($run, 'Cancelled by administrator');

        $this->assertSame(MarketingProviderSyncRun::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame('Cancelled by administrator', $cancelled->message);
        $this->assertNotNull($cancelled->finished_at);
    }

    public function test_history_and_integration_ui_are_tenant_isolated(): void
    {
        config()->set('marketing.providers.catalog.shared_sync', [
            'name' => 'Shared Sync',
            'channel' => 'paid_social',
        ]);

        [$userA, $orgA, $providerA] = $this->connectedProvider('shared_sync');
        $runA = $this->providers->startSynchronization(
            $providerA,
            MarketingProviderSyncRun::TYPE_LEAD_IMPORT,
            MarketingProviderSyncRun::DIRECTION_INBOUND,
        );
        $this->providers->finishSynchronization($runA, MarketingProviderSyncRun::STATUS_COMPLETED);

        $userB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->addMember($userB, 'organization-owner');
        app(TenantContext::class)->set($orgB);
        $providerB = $this->providers->registerProvider($orgB, 'shared_sync');
        $this->providers->storeCredentials($providerB, [
            'access_token' => 'org-b-token',
            'expires_at' => now()->addHour(),
        ]);
        $runB = $this->providers->startSynchronization(
            $providerB,
            MarketingProviderSyncRun::TYPE_CONVERSION_UPLOAD,
            MarketingProviderSyncRun::DIRECTION_OUTBOUND,
        );
        $this->providers->finishSynchronization($runB, MarketingProviderSyncRun::STATUS_FAILED, 'Org B only');

        $this->assertCount(1, $this->providers->synchronizationHistory($providerB));
        $this->assertSame($runB->id, $this->providers->synchronizationHistory($providerB)->first()->id);

        $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->get(route('integrations.show', ['provider' => 'shared_sync']))
            ->assertOk()
            ->assertSee('Synchronization History')
            ->assertSee('Conversion Upload')
            ->assertDontSee('Lead Import');

        app(TenantContext::class)->set($orgA);
        $this->assertCount(1, $this->providers->synchronizationHistory($providerA));
        $this->assertSame($runA->id, $this->providers->synchronizationHistory($providerA)->first()->id);
        $this->assertNotSame($userA->id, $userB->id);
    }
}
