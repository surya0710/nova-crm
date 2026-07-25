<?php

namespace App\Services\Documentation\Validators;

use App\Services\Documentation\ValidationIssue;
use App\Services\DocumentationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LinkValidator
{
    public function __construct(
        private readonly DocumentationService $documentation,
    ) {}

    /**
     * @return array<int, ValidationIssue>
     */
    public function validate(): array
    {
        $issues = [];

        foreach ($this->validationDocuments() as $document) {
            $issues = array_merge(
                $issues,
                $this->validateDocumentLinks($document),
            );
        }

        $issues = array_merge($issues, $this->validateRouteMappings());

        return $issues;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function validationDocuments(): Collection
    {
        $modules = $this->documentation->getConfiguredEnabledModuleKeys();

        return $this->documentation->discoverAllDocuments()
            ->filter(fn (array $document): bool => $modules->contains($document['module']));
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<int, ValidationIssue>
     */
    private function validateDocumentLinks(array $document): array
    {
        $issues = [];
        $content = (string) $document['content'];
        $currentPath = (string) $document['path'];
        $slug = (string) $document['slug'];

        if (! preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $target = trim($match[2]);
            if ($this->shouldIgnoreLink($target)) {
                continue;
            }

            if (str_starts_with($target, '#')) {
                continue;
            }

            [$path, $anchor] = $this->splitTarget($target);
            $resolvedSlug = $this->resolveSlug($currentPath, $path);

            if ($resolvedSlug === null) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'broken_link',
                    message: sprintf('Broken link in [%s]: target [%s] could not be resolved.', $slug, $target),
                    module: $document['module'],
                    slug: $slug,
                    context: ['target' => $target, 'link_text' => $match[1]],
                );

                continue;
            }

            $targetDocument = $this->documentation->findDiscoveredDocument($resolvedSlug);
            if ($targetDocument === null) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'broken_link',
                    message: sprintf('Broken link in [%s]: document [%s] does not exist.', $slug, $resolvedSlug),
                    module: $document['module'],
                    slug: $slug,
                    context: ['target' => $target, 'resolved_slug' => $resolvedSlug],
                );

                continue;
            }

            $targetModule = (string) $targetDocument['module'];
            if (! $this->documentation->isDocumentationModuleEnabled($targetModule)) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'broken_link',
                    message: sprintf('Broken link in [%s]: destination module [%s] is disabled.', $slug, $targetModule),
                    module: $document['module'],
                    slug: $slug,
                    context: ['target' => $target, 'resolved_slug' => $resolvedSlug],
                );
            }

            if ($anchor !== null && $anchor !== '' && ! $this->documentation->validateAnchor($resolvedSlug, $anchor)) {
                $issues[] = new ValidationIssue(
                    type: 'warning',
                    code: 'invalid_anchor',
                    message: sprintf('Invalid anchor in [%s]: #%s does not exist in [%s].', $slug, $anchor, $resolvedSlug),
                    module: $document['module'],
                    slug: $slug,
                    context: ['target' => $target, 'anchor' => $anchor, 'resolved_slug' => $resolvedSlug],
                );
            }
        }

        return $issues;
    }

    /**
     * @return array<int, ValidationIssue>
     */
    private function validateRouteMappings(): array
    {
        $issues = [];
        $mappings = config('documentation.route_mappings', []);
        if (! is_array($mappings)) {
            return [];
        }

        foreach ($mappings as $routeName => $mapping) {
            $slug = is_array($mapping) ? ($mapping['slug'] ?? null) : $mapping;
            if (! is_string($slug) || $slug === '') {
                continue;
            }

            $document = $this->documentation->findDiscoveredDocument($slug);
            if ($document === null) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'broken_route_mapping',
                    message: sprintf('Route mapping [%s] points to missing document [%s].', $routeName, $slug),
                    slug: $slug,
                    context: ['route' => $routeName],
                );

                continue;
            }

            $module = explode('/', $slug)[0] ?? '';
            if ($module !== '' && ! $this->documentation->isDocumentationModuleEnabled($module)) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'broken_route_mapping',
                    message: sprintf('Route mapping [%s] points to disabled module [%s].', $routeName, $module),
                    module: $module,
                    slug: $slug,
                    context: ['route' => $routeName],
                );
            }

            $anchor = is_array($mapping) ? ($mapping['anchor'] ?? null) : null;
            if (is_string($anchor) && $anchor !== '' && ! $this->documentation->validateAnchor($slug, $anchor)) {
                $issues[] = new ValidationIssue(
                    type: 'warning',
                    code: 'invalid_anchor',
                    message: sprintf('Route mapping [%s] references invalid anchor #%s in [%s].', $routeName, ltrim($anchor, '#'), $slug),
                    module: $module ?: null,
                    slug: $slug,
                    context: ['route' => $routeName, 'anchor' => $anchor],
                );
            }
        }

        return $issues;
    }

    private function shouldIgnoreLink(string $target): bool
    {
        if ($target === '' || str_contains($target, '://') || str_starts_with($target, 'mailto:')) {
            return true;
        }

        $ignored = config('documentation.validation.ignored_links', []);
        if (! is_array($ignored)) {
            return false;
        }

        foreach ($ignored as $pattern) {
            if (is_string($pattern) && Str::is($pattern, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitTarget(string $target): array
    {
        $anchorPosition = strpos($target, '#');
        if ($anchorPosition === false) {
            return [$target, null];
        }

        return [
            substr($target, 0, $anchorPosition),
            ltrim(substr($target, $anchorPosition), '#'),
        ];
    }

    private function resolveSlug(string $currentPath, string $path): ?string
    {
        return $this->documentation->resolveDocumentationSlug($currentPath, $path);
    }
}
