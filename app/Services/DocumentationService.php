<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class DocumentationService
{
    public function getSidebarModules(User $user): Collection
    {
        return $this->discoverModules()
            ->filter(fn (array $module): bool => $this->canAccessModule($user, $module['key']))
            ->values();
    }

    public function getNavigationTree(User $user, ?string $activeModule = null, ?string $activeSlug = null): Collection
    {
        return $this->getSidebarModules($user)
            ->map(function (array $module) use ($user, $activeModule, $activeSlug): array {
                $pages = $this->getModuleDocuments($user, $module['key'])
                    ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn (array $page): array => [
                        'title' => $page['title'],
                        'url' => $page['url'],
                        'slug' => $page['slug'],
                        'active' => $activeSlug !== null && $page['slug'] === $activeSlug,
                    ]);

                return [
                    'key' => $module['key'],
                    'title' => $module['title'],
                    'url' => $module['url'],
                    'icon' => $module['icon'],
                    'active' => $activeModule === $module['key'],
                    'expanded' => $activeModule === $module['key'],
                    'pages' => $pages,
                ];
            });
    }

    public function getModuleDocuments(User $user, string $module): Collection
    {
        return $this->discoverDocuments()
            ->filter(fn (array $doc): bool => $doc['module'] === $module)
            ->filter(fn (array $doc): bool => $this->canAccessModule($user, $doc['module']))
            ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function findDocument(User $user, string $module, ?string $page = null): ?array
    {
        if (! $this->isModuleEnabled($module) || ! $this->canAccessModule($user, $module)) {
            return null;
        }

        $slug = $this->buildRequestedSlug($module, $page);
        $document = $this->getModuleDocuments($user, $module)
            ->first(fn (array $item): bool => $item['slug'] === $slug);

        if (! $document && $page === null) {
            $document = $this->getModuleDocuments($user, $module)->first();
        }

        if (! is_array($document)) {
            return null;
        }

        $headings = $document['headings'];
        $resolvedContent = $this->resolveInternalLinks($document['content'], $document['path']);
        $html = $this->renderMarkdown($resolvedContent, $document['path'], (int) $document['mtime']);
        $html = $this->addHeadingIdsToHtml($html, $headings);

        $document['html'] = $html;
        $document['toc'] = $this->buildTableOfContents($headings);
        $document['breadcrumbs'] = $this->buildBreadcrumbs($document);
        $document['metadata'] = $this->getDocumentMetadata($document['slug'], $document);

        return $document;
    }

    public function search(User $user, string $query): Collection
    {
        $needle = Str::lower(trim($query));
        $minLength = max(1, (int) config('documentation.search.min_length', 2));

        if (mb_strlen($needle) < $minLength) {
            return collect();
        }

        $maxResults = max(1, (int) config('documentation.search.max_results', 30));

        return $this->buildSearchIndex()
            ->filter(fn (array $entry): bool => $this->canAccessModule($user, $entry['module']))
            ->filter(fn (array $entry): bool => $this->isModuleSearchable($entry['module']))
            ->map(fn (array $entry): array => $this->scoreSearchResult($entry, $needle))
            ->filter(fn (array $result): bool => $result['score'] > 0)
            ->sortByDesc('score')
            ->take($maxResults)
            ->values()
            ->map(function (array $result) use ($needle): array {
                $result['snippet'] = $this->highlightQuery($result['snippet'], $needle);

                return $result;
            });
    }

    public function resolvePreviousNext(User $user, array $document): array
    {
        $documents = $this->getModuleDocuments($user, $document['module'])->values();
        $index = $documents->search(fn (array $item): bool => $item['slug'] === $document['slug']);

        if (! is_int($index)) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $documents->get($index - 1),
            'next' => $documents->get($index + 1),
        ];
    }

    public function resolveContextForRoute(User $user, ?string $routeName): array
    {
        if (! $this->isHelpIntegrationEnabled() || $routeName === null || $routeName === '') {
            return $this->unavailableContext();
        }

        if (! $this->isRouteIntegrationEnabled($routeName)) {
            return $this->unavailableContext();
        }

        $mapping = $this->resolveRouteMapping($routeName);
        if ($mapping === null) {
            return $this->unavailableContext();
        }

        $context = $this->resolveContextForSlug($user, $mapping['slug'], $mapping['anchor'] ?? null);
        if (! $context['available']) {
            return $context;
        }

        $context['route'] = $routeName;

        return $context;
    }

    public function isHelpAvailable(User $user, ?string $routeName): bool
    {
        return $this->resolveContextForRoute($user, $routeName)['available'];
    }

    public function resolveHelpTargets(User $user, ?string $routeName): Collection
    {
        if ($routeName === null || $routeName === '' || ! $this->isHelpIntegrationEnabled()) {
            return collect();
        }

        $targets = config('documentation.route_help_targets', []);
        $routeTargets = is_array($targets[$routeName] ?? null) ? $targets[$routeName] : [];
        if ($routeTargets === []) {
            $context = $this->resolveContextForRoute($user, $routeName);

            return $context['available']
                ? collect([$this->formatHelpTarget($context)])
                : collect();
        }

        return collect($routeTargets)
            ->map(function (mixed $slug, string $category) use ($user): ?array {
                if (! is_string($slug) || $slug === '') {
                    return null;
                }

                $context = $this->resolveContextForSlug($user, $slug);
                if (! $context['available']) {
                    return null;
                }

                return [
                    'category' => $category,
                    'label' => $this->helpCategoryLabel($category),
                    'title' => $context['title'],
                    'url' => $context['url'],
                    'slug' => $context['slug'],
                ];
            })
            ->filter()
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function getDocumentMetadata(string $slug, ?array $document = null): array
    {
        return $this->cache()->remember(
            $this->metadataCacheKey($slug),
            now()->addSeconds($this->cacheTtl()),
            function () use ($slug, $document): array {
                $document ??= $this->discoverDocuments()->firstWhere('slug', $slug);
                if (! is_array($document)) {
                    return [];
                }

                $parts = explode('/', $slug);
                $module = $parts[0] ?? '';
                $category = $parts[1] ?? (string) config('documentation.help.default_category', 'user-guide');

                /** @var array<string, mixed> $configured */
                $metadata = config('documentation.document_metadata', []);
                $configured = is_array($metadata) ? (array) ($metadata[$slug] ?? []) : [];

                return [
                    'title' => $document['title'],
                    'module' => $module,
                    'page' => Str::after($slug, $module.'/'),
                    'category' => is_string($configured['category'] ?? null) ? $configured['category'] : $category,
                    'keywords' => array_values(array_filter((array) ($configured['keywords'] ?? []))),
                    'icon' => (string) ($configured['icon'] ?? config("documentation.modules.{$module}.icon", 'book')),
                    'related' => array_values(array_filter((array) ($configured['related'] ?? []))),
                ];
            }
        );
    }

    public function getRelatedDocuments(User $user, array $document): Collection
    {
        $metadata = $document['metadata'] ?? $this->getDocumentMetadata($document['slug'], $document);
        $relatedSlugs = collect($metadata['related'] ?? [])
            ->filter(fn ($slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->reject(fn (string $slug): bool => $slug === $document['slug'])
            ->values();

        return $relatedSlugs
            ->map(function (string $slug) use ($user): ?array {
                $related = $this->discoverDocuments()->firstWhere('slug', $slug);
                if (! is_array($related) || ! $this->canAccessModule($user, $related['module'])) {
                    return null;
                }

                $meta = $this->getDocumentMetadata($slug, $related);

                return [
                    'title' => $related['title'],
                    'module' => $related['module'],
                    'module_name' => $this->moduleName($related['module']),
                    'category' => $meta['category'] ?? 'general',
                    'url' => $related['url'],
                    'slug' => $slug,
                ];
            })
            ->filter()
            ->sortBy([
                ['module', 'asc'],
                ['title', 'asc'],
            ])
            ->values();
    }

    public function buildDeepLink(User $user, string $slug, ?string $anchor = null): ?string
    {
        $context = $this->resolveContextForSlug($user, $slug);
        if (! $context['available']) {
            return null;
        }

        $url = $context['url'];
        if ($anchor !== null && $anchor !== '' && $this->validateAnchor($slug, $anchor)) {
            $url .= '#'.ltrim($anchor, '#');
        }

        return $url;
    }

    public function validateAnchor(string $slug, string $anchor): bool
    {
        $anchor = ltrim($anchor, '#');
        $document = $this->discoverDocuments()->firstWhere('slug', $slug);
        if (! is_array($document)) {
            return false;
        }

        return collect($document['headings'] ?? [])
            ->contains(fn (array $heading): bool => $heading['anchor'] === $anchor);
    }

    public function discoverAllDocuments(): Collection
    {
        return $this->discoverDocuments();
    }

    public function getDocumentationFingerprint(): string
    {
        return $this->docsFingerprint();
    }

    public function getEnabledModuleKeys(): Collection
    {
        return $this->discoverModules()->pluck('key');
    }

    public function findDiscoveredDocument(string $slug): ?array
    {
        $document = $this->discoverDocuments()->firstWhere('slug', $slug);

        return is_array($document) ? $document : null;
    }

    public function getConfiguredEnabledModuleKeys(): Collection
    {
        return collect(config('documentation.modules', []))
            ->filter(function (mixed $definition): bool {
                if (! is_array($definition)) {
                    return false;
                }

                return ($definition['enabled'] ?? true) !== false;
            })
            ->keys()
            ->values();
    }

    public function isDocumentationModuleEnabled(string $module): bool
    {
        return $this->isModuleEnabled($module);
    }

    public function getDocumentationRootPath(): string
    {
        return $this->documentationRootPath();
    }

    public function isHiddenDocumentation(string $relativePath): bool
    {
        return $this->isHiddenDocument($relativePath);
    }

    public function resolveDocumentationLink(string $currentPath, string $target): ?string
    {
        return $this->resolveRelativeLink($currentPath, $target);
    }

    public function resolveDocumentationSlug(string $currentPath, string $target): ?string
    {
        $currentDir = str_replace('\\', '/', dirname($currentPath));
        $normalizedTarget = str_replace('\\', '/', $target);
        $normalizedTarget = Str::of($normalizedTarget)->before('?')->before('#')->toString();

        if (str_starts_with($normalizedTarget, '/')) {
            $resolved = ltrim($normalizedTarget, '/');
        } else {
            $resolved = $this->normalizePath($currentDir.'/'.$normalizedTarget);
        }

        $resolved = Str::of($resolved)->replaceMatches('/\.md$/', '')->trim('/')->toString();

        if ($resolved === '' || str_contains($resolved, '..')) {
            return null;
        }

        return $this->findDiscoveredDocument($resolved) !== null ? $resolved : null;
    }

    public function recordRecentlyViewed(array $document): void
    {
        $sessionKey = (string) config('documentation.help.session_key', 'knowledge.recently_viewed');
        $limit = max(1, (int) config('documentation.help.recently_viewed_limit', 5));

        $entry = [
            'slug' => $document['slug'],
            'title' => $document['title'],
            'url' => $document['url'],
            'module' => $document['module'],
            'viewed_at' => now()->toIso8601String(),
        ];

        $recent = collect(Session::get($sessionKey, []))
            ->reject(fn (array $item): bool => ($item['slug'] ?? null) === $document['slug'])
            ->prepend($entry)
            ->take($limit)
            ->values()
            ->all();

        Session::put($sessionKey, $recent);
    }

    public function getRecentlyViewed(User $user): Collection
    {
        $sessionKey = (string) config('documentation.help.session_key', 'knowledge.recently_viewed');

        return collect(Session::get($sessionKey, []))
            ->map(function (array $item) use ($user): ?array {
                $slug = $item['slug'] ?? null;
                if (! is_string($slug) || $slug === '') {
                    return null;
                }

                $document = $this->discoverDocuments()->firstWhere('slug', $slug);
                if (! is_array($document) || ! $this->canAccessModule($user, $document['module'])) {
                    return null;
                }

                return [
                    'slug' => $slug,
                    'title' => $document['title'],
                    'url' => $document['url'],
                    'module' => $document['module'],
                    'module_name' => $this->moduleName($document['module']),
                    'viewed_at' => $item['viewed_at'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    public function contextualHelpForRoute(string $routeName): ?string
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return null;
        }

        $context = $this->resolveContextForRoute($user, $routeName);

        return $context['available'] ? $context['url'] : null;
    }

    /**
     * @return array{available: bool, status: string, url: ?string, title: ?string, breadcrumb: array<int, string>, slug: ?string, anchor: ?string}
     */
    private function resolveContextForSlug(User $user, string $slug, ?string $anchor = null): array
    {
        [$module, $page] = array_pad(explode('/', $slug, 2), 2, null);
        if ($module === null || $page === null) {
            return $this->unavailableContext();
        }

        $document = $this->discoverDocuments()->firstWhere('slug', $slug);
        if (! is_array($document) || ! $this->canAccessModule($user, $module) || ! $this->isModuleEnabled($module)) {
            return $this->unavailableContext();
        }

        $url = $this->urlForSlug($slug);
        if ($anchor !== null && $anchor !== '' && $this->validateAnchor($slug, $anchor)) {
            $url .= '#'.ltrim($anchor, '#');
        }

        return [
            'available' => true,
            'status' => 'available',
            'url' => $url,
            'title' => $document['title'],
            'breadcrumb' => [
                $this->moduleName($module),
                $document['title'],
            ],
            'slug' => $slug,
            'anchor' => $anchor,
        ];
    }

    /**
     * @return array{available: bool, status: string, url: null, title: null, breadcrumb: array<int, string>, slug: null, anchor: null}
     */
    private function unavailableContext(): array
    {
        return [
            'available' => false,
            'status' => 'missing',
            'url' => null,
            'title' => null,
            'breadcrumb' => [],
            'slug' => null,
            'anchor' => null,
        ];
    }

    /**
     * @return array{slug: string, anchor: ?string}|null
     */
    private function resolveRouteMapping(string $routeName): ?array
    {
        return $this->cache()->remember(
            $this->routeMappingsCacheKey().':'.$routeName,
            now()->addSeconds($this->cacheTtl()),
            function () use ($routeName): ?array {
                $mappings = config('documentation.route_mappings', []);
                $mapping = is_array($mappings) ? ($mappings[$routeName] ?? null) : null;

                if (is_string($mapping) && $mapping !== '') {
                    return ['slug' => $mapping, 'anchor' => null];
                }

                if (is_array($mapping) && is_string($mapping['slug'] ?? null) && $mapping['slug'] !== '') {
                    return [
                        'slug' => $mapping['slug'],
                        'anchor' => is_string($mapping['anchor'] ?? null) ? $mapping['anchor'] : null,
                    ];
                }

                $legacyMappings = config('documentation.context_help', []);
                $legacy = is_array($legacyMappings) ? ($legacyMappings[$routeName] ?? null) : null;
                if (is_string($legacy) && $legacy !== '') {
                    return ['slug' => $legacy, 'anchor' => null];
                }

                return null;
            }
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function formatHelpTarget(array $context): array
    {
        $slug = (string) $context['slug'];
        $category = explode('/', $slug)[1] ?? (string) config('documentation.help.default_category', 'user-guide');

        return [
            'category' => $category,
            'label' => $this->helpCategoryLabel($category),
            'title' => $context['title'],
            'url' => $context['url'],
            'slug' => $slug,
        ];
    }

    private function helpCategoryLabel(string $category): string
    {
        $label = config("documentation.help_categories.{$category}");

        return is_string($label) && $label !== ''
            ? $label
            : Str::of($category)->replace('-', ' ')->title()->toString();
    }

    private function isHelpIntegrationEnabled(): bool
    {
        return config('documentation.help.enabled', true) !== false;
    }

    private function isRouteIntegrationEnabled(string $routeName): bool
    {
        $integrations = config('documentation.integrations', []);
        if (! is_array($integrations) || $integrations === []) {
            return true;
        }

        return in_array($routeName, $integrations, true);
    }

    private function routeMappingsCacheKey(): string
    {
        return 'knowledge-center:route-mappings:'.md5((string) json_encode(config('documentation.route_mappings', [])));
    }

    private function metadataCacheKey(string $slug): string
    {
        return 'knowledge-center:metadata:'.md5($slug.'|'.$this->docsFingerprint());
    }

    public function buildBreadcrumbs(array $document): array
    {
        $breadcrumbs = [
            [
                'title' => 'Knowledge Center',
                'url' => route('knowledge.index'),
            ],
            [
                'title' => $this->moduleName($document['module']),
                'url' => route('knowledge.module', ['module' => $document['module']]),
            ],
        ];

        $slugParts = explode('/', $document['slug']);
        array_shift($slugParts);

        if ($slugParts !== []) {
            $lastIndex = count($slugParts) - 1;
            $running = [$document['module']];

            foreach ($slugParts as $index => $part) {
                $running[] = $part;
                $isLast = $index === $lastIndex;
                $segmentSlug = implode('/', $running);

                $breadcrumbs[] = [
                    'title' => $isLast
                        ? $document['title']
                        : Str::of($part)->replace('-', ' ')->title()->toString(),
                    'url' => $isLast ? null : $this->urlForSlugIfExists($segmentSlug),
                ];
            }
        } else {
            $breadcrumbs[] = [
                'title' => $document['title'],
                'url' => null,
            ];
        }

        return $breadcrumbs;
    }

    public function buildTableOfContents(array $headings): array
    {
        return collect($headings)
            ->filter(fn (array $heading): bool => $heading['level'] <= 3)
            ->values()
            ->all();
    }

    public function highlightQuery(string $text, string $query): string
    {
        $escaped = preg_quote($query, '/');

        return (string) preg_replace(
            '/('.$escaped.')/iu',
            '<mark class="rounded bg-yellow-100 px-0.5">$1</mark>',
            e($text)
        );
    }

    public function resolveInternalLinks(string $content, string $currentPath): string
    {
        return (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function (array $matches) use ($currentPath): string {
                $label = $matches[1];
                $target = trim($matches[2]);

                if ($target === '' || str_contains($target, '://') || str_starts_with($target, 'mailto:')) {
                    return $matches[0];
                }

                if (str_starts_with($target, '#')) {
                    return $matches[0];
                }

                $resolved = $this->resolveRelativeLink($currentPath, $target);
                if ($resolved === null) {
                    return '['.$label.']('.$target.')';
                }

                return '['.$label.']('.$resolved.')';
            },
            $content
        );
    }

    private function buildSearchIndex(): Collection
    {
        return $this->cache()->remember(
            $this->searchIndexCacheKey(),
            now()->addSeconds($this->cacheTtl()),
            function (): Collection {
                return $this->discoverDocuments()
                    ->map(function (array $document): array {
                        $plainBody = $this->stripMarkdown($document['content']);

                        return [
                            'module' => $document['module'],
                            'module_name' => $this->moduleName($document['module']),
                            'slug' => $document['slug'],
                            'page' => Str::after($document['slug'], $document['module'].'/'),
                            'title' => $document['title'],
                            'headings' => $document['headings'],
                            'content' => $document['content'],
                            'plain_body' => $plainBody,
                            'url' => $document['url'],
                            'path' => $document['path'],
                        ];
                    })
                    ->values();
            }
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function scoreSearchResult(array $entry, string $needle): array
    {
        $score = 0.0;
        $matchingHeading = null;
        $titleLower = Str::lower($entry['title']);

        if ($titleLower === $needle) {
            $score += 100;
        } elseif (str_contains($titleLower, $needle)) {
            $score += 60;
        }

        foreach ($entry['headings'] as $heading) {
            $headingLower = Str::lower($heading['title']);
            if (str_contains($headingLower, $needle)) {
                $score += 40 - ($heading['level'] * 5);
                $matchingHeading ??= $heading['title'];
            }
        }

        $bodyLower = Str::lower($entry['plain_body']);
        $occurrences = substr_count($bodyLower, $needle);
        if ($occurrences > 0) {
            $score += min(30, $occurrences * 5);
        }

        $snippetSource = $entry['plain_body'];

        if ($matchingHeading !== null) {
            $snippetSource = $this->extractHeadingContext($entry['content'], $matchingHeading) ?? $entry['plain_body'];
        }

        return [
            'module' => $entry['module'],
            'module_name' => $entry['module_name'],
            'page' => $entry['page'],
            'title' => $entry['title'],
            'heading' => $matchingHeading,
            'snippet' => $this->generateSnippet($snippetSource, $needle),
            'score' => $score,
            'url' => $entry['url'],
        ];
    }

    private function generateSnippet(string $content, string $needle): string
    {
        $plain = $this->stripMarkdown($content);
        $length = max(40, (int) config('documentation.search.snippet_length', 160));
        $lowerPlain = Str::lower($plain);
        $position = mb_strpos($lowerPlain, $needle);

        if ($position === false) {
            return Str::limit($plain, $length);
        }

        $start = max(0, $position - (int) floor($length / 3));
        $snippet = mb_substr($plain, $start, $length);

        if ($start > 0) {
            $snippet = '...'.ltrim($snippet);
        }

        if ($start + $length < mb_strlen($plain)) {
            $snippet = rtrim($snippet).'...';
        }

        return trim($snippet);
    }

    private function extractHeadingContext(string $markdown, string $headingTitle): ?string
    {
        $pattern = '/^#{1,6}\s+'.preg_quote($headingTitle, '/').'\s*$(.*?)(?=^#{1,6}\s|\z)/ms';

        if (! preg_match($pattern, $markdown, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function stripMarkdown(string $content): string
    {
        $plain = preg_replace('/```.*?```/s', ' ', $content) ?? $content;
        $plain = preg_replace('/`([^`]+)`/', '$1', $plain) ?? $plain;
        $plain = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $plain) ?? $plain;
        $plain = preg_replace('/^#{1,6}\s+/m', '', $plain) ?? $plain;
        $plain = preg_replace('/^[\-*+]\s+/m', '', $plain) ?? $plain;
        $plain = preg_replace('/[*_~]+/', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;

        return trim($plain);
    }

    private function discoverModules(): Collection
    {
        return $this->cache()->remember(
            $this->modulesCacheKey(),
            now()->addSeconds($this->cacheTtl()),
            function (): Collection {
                $configured = collect(config('documentation.modules', []));
                $order = collect(config('documentation.sidebar_order', []))
                    ->filter(fn ($value): bool => is_string($value) && $value !== '')
                    ->values();
                $existing = $this->discoverExistingModuleKeys();
                $orderedKeys = $order->merge($existing)->unique()->values();

                return $orderedKeys
                    ->filter(fn (string $module): bool => $this->isModuleEnabled($module))
                    ->filter(fn (string $module): bool => $existing->contains($module))
                    ->map(function (string $module) use ($configured): array {
                        /** @var array<string, mixed> $definition */
                        $definition = (array) $configured->get($module, []);

                        return [
                            'key' => $module,
                            'title' => (string) ($definition['name'] ?? Str::of($module)->replace('-', ' ')->title()),
                            'icon' => (string) ($definition['icon'] ?? 'book'),
                            'url' => route('knowledge.module', ['module' => $module]),
                        ];
                    })
                    ->values();
            }
        );
    }

    private function discoverDocuments(): Collection
    {
        return $this->cache()->remember(
            $this->documentsCacheKey(),
            now()->addSeconds($this->cacheTtl()),
            function (): Collection {
                $root = $this->documentationRootPath();
                if (! File::isDirectory($root)) {
                    return collect();
                }

                /** @var array<int, array<string, mixed>> $documents */
                $documents = [];

                /** @var SplFileInfo $file */
                foreach (File::allFiles($root) as $file) {
                    if ($file->getExtension() !== 'md') {
                        continue;
                    }

                    $relativePath = $this->relativePath($file->getPathname());
                    if ($relativePath === null || $this->isHiddenDocument($relativePath)) {
                        continue;
                    }

                    $slug = Str::of($relativePath)->beforeLast('.md')->toString();
                    $module = explode('/', $slug)[0] ?? '';

                    if ($module === '' || ! $this->isModuleEnabled($module)) {
                        continue;
                    }

                    $content = $this->loadMarkdown($relativePath);
                    if ($content === null) {
                        continue;
                    }

                    $headings = $this->extractHeadings($content);

                    $documents[] = [
                        'slug' => $slug,
                        'module' => $module,
                        'path' => $relativePath,
                        'title' => $this->buildTitle($slug, $content),
                        'url' => $this->urlForSlug($slug),
                        'content' => $content,
                        'headings' => $headings,
                        'mtime' => $file->getMTime(),
                    ];
                }

                return collect($documents)
                    ->sortBy([
                        ['module', 'asc'],
                        ['slug', 'asc'],
                    ])
                    ->values();
            }
        );
    }

    private function discoverExistingModuleKeys(): Collection
    {
        return $this->discoverDocuments()
            ->pluck('module')
            ->unique()
            ->values();
    }

    private function loadMarkdown(string $relativePath): ?string
    {
        $fullPath = $this->resolvedPath($relativePath);
        if (! $fullPath || ! File::isFile($fullPath)) {
            return null;
        }

        return File::get($fullPath);
    }

    private function renderMarkdown(string $content, string $path, int $mtime): string
    {
        return $this->cache()->remember(
            $this->renderCacheKey($path, $mtime),
            now()->addSeconds($this->cacheTtl()),
            fn (): string => Str::markdown($content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
                'renderer' => [
                    'block_separator' => PHP_EOL,
                    'inner_separator' => PHP_EOL,
                    'soft_break' => PHP_EOL,
                ],
            ])
        );
    }

    /**
     * @return array<int, array{level: int, title: string, anchor: string}>
     */
    private function extractHeadings(string $content): array
    {
        preg_match_all('/^(#{1,3})\s+(.+)$/m', $content, $matches, PREG_SET_ORDER);

        $headings = [];
        $anchorCounts = [];

        foreach ($matches as $match) {
            $title = trim($match[2]);
            $baseAnchor = Str::slug($title);
            $anchorCounts[$baseAnchor] = ($anchorCounts[$baseAnchor] ?? 0) + 1;
            $anchor = $anchorCounts[$baseAnchor] > 1
                ? $baseAnchor.'-'.$anchorCounts[$baseAnchor]
                : $baseAnchor;

            $headings[] = [
                'level' => strlen($match[1]),
                'title' => $title,
                'anchor' => $anchor,
            ];
        }

        return $headings;
    }

    /**
     * @param  array<int, array{level: int, title: string, anchor: string}>  $headings
     */
    private function addHeadingIdsToHtml(string $html, array $headings): string
    {
        foreach ($headings as $heading) {
            if ($heading['level'] > 3) {
                continue;
            }

            $pattern = '/<h'.$heading['level'].'>(.*?)<\/h'.$heading['level'].'>/';
            $replacement = '<h'.$heading['level'].' id="'.$heading['anchor'].'">$1</h'.$heading['level'].'>';
            $html = (string) preg_replace($pattern, $replacement, $html, 1);
        }

        return $html;
    }

    private function resolveRelativeLink(string $currentPath, string $target): ?string
    {
        $currentDir = str_replace('\\', '/', dirname($currentPath));
        $normalizedTarget = str_replace('\\', '/', $target);

        if (str_starts_with($normalizedTarget, '/')) {
            $resolved = ltrim($normalizedTarget, '/');
        } else {
            $resolved = $this->normalizePath($currentDir.'/'.$normalizedTarget);
        }

        $resolved = Str::of($resolved)->before('?')->before('#')->toString();
        $resolved = Str::of($resolved)->replaceMatches('/\.md$/', '')->toString();
        $resolved = trim($resolved, '/');

        if ($resolved === '' || str_contains($resolved, '..')) {
            return null;
        }

        $fullPath = $this->resolvedPath($resolved.'.md');
        if ($fullPath === null) {
            return null;
        }

        return $this->urlForSlug($resolved);
    }

    private function normalizePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function buildRequestedSlug(string $module, ?string $page): string
    {
        if ($page === null || trim($page) === '') {
            return $module.'/overview';
        }

        return trim($module.'/'.trim($page, '/'), '/');
    }

    private function buildTitle(string $slug, string $content): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim((string) $matches[1]);
        }

        return Str::of($slug)->afterLast('/')->replace('-', ' ')->title()->toString();
    }

    private function urlForSlugIfExists(string $slug): ?string
    {
        $exists = $this->discoverDocuments()->contains(fn (array $doc): bool => $doc['slug'] === $slug);

        return $exists ? $this->urlForSlug($slug) : null;
    }

    private function canAccessModule(User $user, string $module): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $permissions = config("documentation.module_permissions.{$module}", []);
        if (! is_array($permissions) || $permissions === []) {
            return true;
        }

        return $user->hasAnyPermission($permissions);
    }

    private function isModuleSearchable(string $module): bool
    {
        $value = config("documentation.modules.{$module}.searchable");

        return $value !== false;
    }

    private function moduleName(string $module): string
    {
        $name = config("documentation.modules.{$module}.name");

        return is_string($name) && $name !== ''
            ? $name
            : Str::of($module)->replace('-', ' ')->title()->toString();
    }

    private function isModuleEnabled(string $module): bool
    {
        $value = config("documentation.modules.{$module}.enabled");

        return $value !== false;
    }

    private function isHiddenDocument(string $relativePath): bool
    {
        $hidden = config('documentation.hidden_documents', []);
        if (! is_array($hidden)) {
            return false;
        }

        foreach ($hidden as $pattern) {
            if (is_string($pattern) && Str::is($pattern, $relativePath)) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $absolutePath): ?string
    {
        $root = $this->documentationRootPath();
        $rootReal = realpath($root);
        $fileReal = realpath($absolutePath);

        if (! is_string($rootReal) || ! is_string($fileReal)) {
            return null;
        }

        $rootPrefix = str_replace('\\', '/', rtrim($rootReal, '\\/')).'/';
        $fileNormalized = str_replace('\\', '/', $fileReal);

        if (! str_starts_with($fileNormalized, $rootPrefix)) {
            return null;
        }

        return ltrim(Str::after($fileNormalized, $rootPrefix), '/');
    }

    private function resolvedPath(string $relativePath): ?string
    {
        $root = $this->documentationRootPath();
        $candidate = $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($relativePath, '/\\'));
        $candidateReal = realpath($candidate);
        $rootReal = realpath($root);

        if (! is_string($candidateReal) || ! is_string($rootReal)) {
            return null;
        }

        $rootPrefix = str_replace('\\', '/', rtrim($rootReal, '\\/')).'/';
        $candidateNormalized = str_replace('\\', '/', $candidateReal);

        return str_starts_with($candidateNormalized, $rootPrefix) ? $candidateReal : null;
    }

    private function documentationRootPath(): string
    {
        return (string) config('documentation.root_path', base_path('docs'));
    }

    private function cacheTtl(): int
    {
        return max(1, (int) config('documentation.cache_ttl', 3600));
    }

    private function cache(): Repository
    {
        $store = (string) config('documentation.cache_store', 'file');

        return Cache::store($store !== '' ? $store : 'file');
    }

    private function modulesCacheKey(): string
    {
        return 'knowledge-center:modules:'.md5((string) $this->docsFingerprint());
    }

    private function documentsCacheKey(): string
    {
        return 'knowledge-center:documents:'.md5((string) $this->docsFingerprint());
    }

    private function searchIndexCacheKey(): string
    {
        return 'knowledge-center:search-index:'.md5((string) $this->docsFingerprint());
    }

    private function renderCacheKey(string $path, int $mtime): string
    {
        return 'knowledge-center:render:'.md5($path.'|'.$mtime);
    }

    private function docsFingerprint(): string
    {
        $root = $this->documentationRootPath();
        if (! File::isDirectory($root)) {
            return 'missing';
        }

        $parts = [];
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $relative = $this->relativePath($file->getPathname());
            if ($relative === null) {
                continue;
            }

            $parts[] = $relative.'|'.$file->getMTime();
        }

        sort($parts);

        return md5(implode("\n", $parts));
    }

    private function urlForSlug(string $slug): string
    {
        [$module, $page] = array_pad(explode('/', $slug, 2), 2, null);

        return $page
            ? route('knowledge.page', ['module' => $module, 'page' => $page])
            : route('knowledge.module', ['module' => $module]);
    }
}
