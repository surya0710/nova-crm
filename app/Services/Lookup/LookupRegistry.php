<?php

namespace App\Services\Lookup;

use App\Contracts\Lookup\LookupProviderInterface;
use InvalidArgumentException;

class LookupRegistry
{
    /** @var array<string, LookupProviderInterface> */
    protected array $providers = [];

    public function register(LookupProviderInterface $provider): void
    {
        $key = $provider->key();

        if ($key === '') {
            throw new InvalidArgumentException('Lookup provider key cannot be empty.');
        }

        $this->providers[$key] = $provider;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    public function resolve(string $key): LookupProviderInterface
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Lookup entity [{$key}] is not registered.");
        }

        return $this->providers[$key];
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->providers);
    }
}
