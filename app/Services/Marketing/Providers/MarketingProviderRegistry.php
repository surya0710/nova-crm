<?php

namespace App\Services\Marketing\Providers;

use App\Contracts\MarketingProviderInterface;
use InvalidArgumentException;

/**
 * In-process registry of marketing provider adapters (P7C.1).
 *
 * Future providers are added by registering an implementation (typically via
 * config/marketing.php providers.drivers). No schema or service changes required.
 */
class MarketingProviderRegistry
{
    /** @var array<string, MarketingProviderInterface> */
    protected array $providers = [];

    public function register(MarketingProviderInterface $provider): void
    {
        $slug = $provider->slug();

        if ($slug === '') {
            throw new InvalidArgumentException('Marketing provider slug cannot be empty.');
        }

        $this->providers[$slug] = $provider;
    }

    public function resolve(string $slug): MarketingProviderInterface
    {
        if (! $this->has($slug)) {
            throw new InvalidArgumentException("Marketing provider [{$slug}] is not registered.");
        }

        return $this->providers[$slug];
    }

    public function has(string $slug): bool
    {
        return isset($this->providers[$slug]);
    }

    /**
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_keys($this->providers);
    }

    /**
     * @return array<string, MarketingProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * Catalog of registered adapters for UI / API discovery.
     *
     * @return list<array{slug: string, name: string, capabilities: list<string>}>
     */
    public function supported(): array
    {
        $supported = [];

        foreach ($this->providers as $provider) {
            $supported[] = [
                'slug' => $provider->slug(),
                'name' => $provider->displayName(),
                'capabilities' => $provider->capabilities(),
            ];
        }

        return $supported;
    }
}
