<?php

namespace App\Services\CommandPalette;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class CommandPaletteRegistry
{
    /** @var array<int, CommandProviderInterface> */
    protected array $providers = [];

    public function register(CommandProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @return Collection<int, array{id: string, label: string, group: string, href?: string|null, action?: string|null, keywords?: array}>
     */
    public function commands(User $user, ?Organization $organization, ?string $query = null): Collection
    {
        $commands = collect($this->providers)
            ->flatMap(fn (CommandProviderInterface $provider) => $provider->commands($user, $organization))
            ->values();

        $query = trim((string) $query);
        if ($query === '') {
            return $commands;
        }

        $needle = mb_strtolower($query);

        return $commands->filter(function (array $command) use ($needle) {
            $parts = array_merge(
                [
                    $command['label'] ?? '',
                    $command['group'] ?? '',
                ],
                $command['keywords'] ?? []
            );
            $haystack = mb_strtolower(implode(' ', array_filter($parts)));

            return str_contains($haystack, $needle);
        })->values();
    }
}
