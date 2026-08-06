<?php

namespace App\Http\Controllers;

use App\Services\MarketingProviderService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Provider-agnostic OAuth connect/callback endpoints (P7C.2).
 *
 * Persistence goes through MarketingProviderService only. Adapters supply
 * authorization URLs and credential payloads via MarketingProviderInterface.
 */
class MarketingProviderOAuthController extends Controller
{
    public function __construct(
        protected MarketingProviderService $providers,
        protected TenantContext $tenant,
    ) {}

    public function connect(Request $request, string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();

        if (! $organization) {
            abort(403, 'Organization context is required.');
        }

        try {
            $this->providers->resolveAdapter($provider);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $connection = $this->providers->registerProvider($organization, $provider);

        try {
            $result = $this->providers->authorize($connection, [
                'phase' => 'start',
            ]);
        } catch (Throwable $e) {
            Log::warning('Marketing provider OAuth start failed', [
                'provider' => $provider,
                'organization_id' => $organization->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('integrations.index')
                ->with('error', 'Unable to start provider connection: '.$e->getMessage());
        }

        $url = $result['authorization_url'] ?? null;

        if (! is_string($url) || $url === '') {
            return redirect()
                ->route('integrations.index')
                ->with('error', 'Provider did not return an authorization URL.');
        }

        if (! empty($result['metadata']['state'])) {
            $request->session()->put($this->stateSessionKey($provider), $result['metadata']['state']);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();

        if (! $organization) {
            abort(403, 'Organization context is required.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('integrations.index')
                ->with('error', 'Provider authorization was denied: '.$request->string('error_description', $request->string('error')));
        }

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection) {
            return redirect()
                ->route('integrations.index')
                ->with('error', 'No provider connection was started for this organization.');
        }

        $state = $request->string('state')->toString();
        $expected = $request->session()->pull($this->stateSessionKey($provider));

        if (is_string($expected) && $expected !== '' && ! hash_equals($expected, $state)) {
            return redirect()
                ->route('integrations.index')
                ->with('error', 'Invalid OAuth state.');
        }

        try {
            $this->providers->authorize($connection, [
                'phase' => 'callback',
                'code' => $request->string('code')->toString(),
                'state' => $state,
            ]);
        } catch (Throwable $e) {
            Log::warning('Marketing provider OAuth callback failed', [
                'provider' => $provider,
                'organization_id' => $organization->id,
                'message' => $e->getMessage(),
            ]);

            $this->providers->markError($connection, $e->getMessage());

            return redirect()
                ->route('integrations.index')
                ->with('error', 'Unable to complete provider connection: '.$e->getMessage());
        }

        return redirect()
            ->route('integrations.index')
            ->with('status', 'integration-connected');
    }

    public function disconnect(string $provider): RedirectResponse
    {
        $organization = $this->tenant->get();

        if (! $organization) {
            abort(403, 'Organization context is required.');
        }

        $connection = $this->providers->findProvider($organization, $provider);

        if (! $connection) {
            return redirect()
                ->route('integrations.index')
                ->with('error', 'Provider is not connected.');
        }

        $this->providers->disconnect($connection);

        return redirect()
            ->route('integrations.index')
            ->with('status', 'integration-disconnected');
    }

    protected function stateSessionKey(string $provider): string
    {
        return 'marketing_oauth_state_'.$provider;
    }
}
