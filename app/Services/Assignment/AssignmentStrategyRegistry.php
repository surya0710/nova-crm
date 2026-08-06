<?php

namespace App\Services\Assignment;

use App\Services\Assignment\Contracts\AssignmentStrategyInterface;
use InvalidArgumentException;

class AssignmentStrategyRegistry
{
    /** @var array<string, AssignmentStrategyInterface> */
    protected array $strategies = [];

    public function register(AssignmentStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->key()] = $strategy;
    }

    public function get(string $key): AssignmentStrategyInterface
    {
        if (! isset($this->strategies[$key])) {
            throw new InvalidArgumentException("Unknown assignment strategy [{$key}].");
        }

        return $this->strategies[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    /**
     * @return array<string, AssignmentStrategyInterface>
     */
    public function all(): array
    {
        return $this->strategies;
    }
}
