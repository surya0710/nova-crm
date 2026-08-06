<?php

namespace App\Services\Recruitment\Providers;

use App\Contracts\RecruitmentProviderInterface;
use InvalidArgumentException;

class RecruitmentProviderRegistry
{
    /** @var array<string, RecruitmentProviderInterface> */
    protected array $providers = [];

    public function register(RecruitmentProviderInterface $provider): void
    {
        $slug = $provider->slug();

        if ($slug === '') {
            throw new InvalidArgumentException('Recruitment provider slug cannot be empty.');
        }

        $this->providers[$slug] = $provider;
    }

    public function resolve(string $slug): RecruitmentProviderInterface
    {
        if (! $this->has($slug)) {
            throw new InvalidArgumentException("Recruitment provider [{$slug}] is not registered.");
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
     * @return array<string, RecruitmentProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * @return list<array{slug: string, name: string, category: string, capabilities: list<string>}>
     */
    public function supported(): array
    {
        $supported = [];

        foreach ($this->providers as $provider) {
            $supported[] = [
                'slug' => $provider->slug(),
                'name' => $provider->displayName(),
                'category' => $provider->category(),
                'capabilities' => $provider->capabilities(),
            ];
        }

        return $supported;
    }
}
