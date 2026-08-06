<?php

namespace App\Services\Navigation;

use Illuminate\Support\Collection;

class BreadcrumbBuilder
{
    /**
     * @param  array<int, array{label: string, href?: string|null}>  $crumbs
     * @return Collection<int, array{label: string, href: string|null, current: bool}>
     */
    public function build(array $crumbs): Collection
    {
        $normalized = collect($crumbs)
            ->filter(fn ($c) => ! empty($c['label']))
            ->values();

        $last = $normalized->count() - 1;

        return $normalized->map(function (array $crumb, int $index) use ($last) {
            return [
                'label' => $crumb['label'],
                'href' => $index === $last ? null : ($crumb['href'] ?? null),
                'current' => $index === $last,
            ];
        });
    }

    /**
     * @param  array{label: string, href?: string|null}|null  $record
     * @return Collection<int, array{label: string, href: string|null, current: bool}>
     */
    public function fromWorkspace(
        string $workspaceLabel,
        ?string $workspaceHref,
        string $sectionLabel,
        ?string $sectionHref = null,
        ?array $record = null,
    ): Collection {
        $crumbs = [
            ['label' => $workspaceLabel, 'href' => $workspaceHref],
            ['label' => $sectionLabel, 'href' => $sectionHref],
        ];

        if ($record) {
            $crumbs[] = $record;
        }

        return $this->build($crumbs);
    }
}
