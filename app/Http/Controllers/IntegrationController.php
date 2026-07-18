<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMarketingProviderAssetsRequest;
use App\Services\Marketing\Providers\MetaWebhookProcessor;
use App\Services\MarketingProviderService;
use App\Services\ProviderDiagnosticsService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Provider-agnostic Integration Management UI.
 *
 * Renders catalog cards and delegates writes to MarketingProviderService.
 * Asset discovery is capability-driven — no Meta/Google-specific branching.
 */
class IntegrationController extends Controller
{
    public function __construct(
        protected MarketingProviderService $providers,
        protected ProviderDiagnosticsService $diagnostics,
        protected TenantContext $tenant,
        protected MetaWebhookProcessor $processor,
    ) {}

    public function index(): View
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('integrations.index', [
            'organization' => $organization,
            'cards' => $this->providers->integrationCardsForOrganization($organization),
        ]);
    }

    public function diagnostics(): View
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return view('integrations.diagnostics', [
            'organization' => $organization,
            'diagnostics' => $this->diagnostics->diagnosticsForOrganization($organization),
        ]);
    }

    public function runHealthCheck(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection) {
            return redirect()
                ->route('integrations.diagnostics')
                ->with('error', __('This integration is not connected.'));
        }

        try {
            $result = $this->diagnostics->runHealthCheck($connection);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('integrations.diagnostics')
                ->with('error', $e->getMessage());
        }

        $health = $result['health_check'];
        $status = ($health['healthy'] ?? false)
            ? 'integration-health-check-healthy'
            : 'integration-health-check-unhealthy';

        return redirect()
            ->route('integrations.diagnostics')
            ->with('status', $status)
            ->with('status_detail', $health['message'] ?? null);
    }

    public function show(string $provider): View
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $details = $this->providers->integrationDetailsForOrganization($organization, $provider);
        abort_unless($details, 404);

        $connection = $this->providers->findProvider($organization, $provider);
        $assetDiscovery = null;
        $supportsAssetDiscovery = $connection !== null
            && $this->providers->supportsAssetDiscovery($connection);
        $supportsLeadFormSync = $connection !== null
            && $this->providers->supportsLeadFormSync($connection);
        $supportsLeadImport = $connection !== null
            && $this->providers->supportsLeadImport($connection);
        $supportsWebhooks = $connection !== null
            && $this->providers->supportsWebhooks($connection);
        $supportsOfflineConversions = $connection !== null
            && $this->providers->supportsOfflineConversions($connection);
        $leadForms = $supportsLeadFormSync && $connection
            ? $this->providers->listLeadForms($connection)
            : collect();
        $lastLeadImport = $supportsLeadImport && $connection
            ? $this->providers->latestLeadImportRun($connection)
            : null;
        $lastConversionUpload = $supportsOfflineConversions && $connection
            ? $this->providers->latestConversionUploadRun($connection)
            : null;
        $webhookStatus = $supportsWebhooks
            ? $this->providers->webhookStatus($provider)
            : null;
        $synchronizationHistory = $connection
            ? $this->providers->synchronizationHistory($connection)
            : collect();

        if ($supportsAssetDiscovery && $connection && $connection->status !== 'disconnected') {
            // Page load is read-only; Refresh / Save update status on failure.
            $assetDiscovery = $this->providers->discoverAssets($connection, [], false);
            $details = $this->providers->integrationDetailsForOrganization($organization, $provider);
        }

        return view('integrations.show', [
            'organization' => $organization,
            'integration' => $details,
            'supportsAssetDiscovery' => $supportsAssetDiscovery,
            'assetDiscovery' => $assetDiscovery,
            'selectedAssets' => $details['configuration'] ?? [],
            'supportsLeadFormSync' => $supportsLeadFormSync,
            'leadForms' => $leadForms,
            'supportsLeadImport' => $supportsLeadImport,
            'lastLeadImport' => $lastLeadImport,
            'supportsOfflineConversions' => $supportsOfflineConversions,
            'lastConversionUpload' => $lastConversionUpload,
            'supportsWebhooks' => $supportsWebhooks,
            'webhookStatus' => $webhookStatus,
            'synchronizationHistory' => $synchronizationHistory,
        ]);
    }

    public function uploadConversions(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection || ! $this->providers->supportsOfflineConversions($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support offline conversion uploads.'));
        }

        try {
            $result = $this->providers->uploadConversions($connection);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $e->getMessage());
        }

        if (! ($result['ok'] ?? false) && ($result['uploaded'] ?? 0) === 0 && ($result['skipped'] ?? 0) === 0) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $result['message'] ?? __('Unable to upload conversions.'));
        }

        $status = ($result['failed'] ?? 0) > 0
            ? 'integration-conversions-uploaded-partial'
            : 'integration-conversions-uploaded';

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', $status)
            ->with('status_detail', $result['message'] ?? null);
    }

    public function importLeads(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);
        $user = request()->user();
        abort_unless($user, 403);

        if (! $connection || ! $this->providers->supportsLeadImport($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support lead import.'));
        }

        try {
            $result = $this->providers->importLeadEntries($connection, $user);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $e->getMessage());
        }

        if (! ($result['ok'] ?? false) && ($result['imported'] ?? 0) === 0 && ($result['skipped'] ?? 0) === 0) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $result['message'] ?? __('Unable to import leads.'));
        }

        $status = ($result['failed'] ?? 0) > 0
            ? 'integration-leads-imported-partial'
            : 'integration-leads-imported';

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', $status)
            ->with('status_detail', $result['message'] ?? null);
    }

    public function processWebhooks(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection || ! $this->providers->supportsWebhooks($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support webhooks.'));
        }

        $summary = $this->processor->processPending($provider);

        if (($summary['events'] ?? 0) === 0) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('status', 'integration-webhooks-empty');
        }

        $status = ($summary['failed'] ?? 0) > 0
            ? 'integration-webhooks-processed-partial'
            : 'integration-webhooks-processed';

        $detail = sprintf(
            __('Processed %d event(s): imported %d, skipped %d, failed %d.'),
            $summary['events'] ?? 0,
            $summary['imported'] ?? 0,
            $summary['skipped'] ?? 0,
            $summary['lead_failed'] ?? 0,
        );

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', $status)
            ->with('status_detail', $detail);
    }

    public function synchronizeLeadForms(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection || ! $this->providers->supportsLeadFormSync($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support lead form synchronization.'));
        }

        try {
            $result = $this->providers->synchronizeLeadForms($connection);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $e->getMessage());
        }

        if (! ($result['ok'] ?? false) && ($result['synced'] ?? 0) === 0) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $result['message'] ?? __('Unable to synchronize lead forms.'));
        }

        $message = ($result['failed'] ?? 0) > 0
            ? 'integration-lead-forms-synced-partial'
            : 'integration-lead-forms-synced';

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', $message)
            ->with('status_detail', $result['message'] ?? null);
    }

    public function saveAssets(SaveMarketingProviderAssetsRequest $request, string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection || ! $this->providers->supportsAssetDiscovery($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support asset selection.'));
        }

        try {
            $this->providers->saveAssetConfiguration($connection, $request->selection());
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', 'integration-assets-saved');
    }

    public function refreshAssets(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection || ! $this->providers->supportsAssetDiscovery($connection)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', __('This integration does not support asset discovery.'));
        }

        $result = $this->providers->discoverAssets($connection);

        if (! ($result['ok'] ?? false)) {
            return redirect()
                ->route('integrations.show', ['provider' => $provider])
                ->with('error', $result['message'] ?? __('Unable to refresh assets.'));
        }

        return redirect()
            ->route('integrations.show', ['provider' => $provider])
            ->with('status', 'integration-assets-refreshed');
    }

    public function disconnect(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection) {
            return redirect()
                ->route('integrations.index')
                ->with('error', __('This integration is not connected.'));
        }

        $this->providers->disconnect($connection);

        return redirect()
            ->route('integrations.index')
            ->with('status', 'integration-disconnected');
    }
}
