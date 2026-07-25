<?php

namespace App\Services;

use App\Services\Documentation\ValidationIssue;
use App\Services\Documentation\Validators\LinkValidator;
use App\Services\Documentation\Validators\MetadataValidator;
use App\Services\Documentation\Validators\ModuleCompletenessValidator;
use App\Services\Documentation\Validators\RelatedDocumentValidator;
use App\Services\Documentation\Validators\ReleaseNotesValidator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DocumentationValidationService
{
    public function __construct(
        private readonly DocumentationService $documentation,
        private readonly ModuleCompletenessValidator $moduleCompletenessValidator,
        private readonly MetadataValidator $metadataValidator,
        private readonly LinkValidator $linkValidator,
        private readonly RelatedDocumentValidator $relatedDocumentValidator,
        private readonly ReleaseNotesValidator $releaseNotesValidator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function validate(bool $useCache = true): array
    {
        if (! config('documentation.validation.enabled', true)) {
            return $this->emptyReport();
        }

        if ($useCache) {
            return Cache::remember(
                $this->validationCacheKey(),
                now()->addSeconds($this->cacheTtl()),
                fn (): array => $this->runValidation(),
            );
        }

        return $this->runValidation();
    }

    /**
     * @return array<string, mixed>
     */
    public function health(bool $useCache = true): array
    {
        $report = $this->validate($useCache);

        return [
            'status' => $report['status'],
            'statistics' => $report['statistics'],
            'coverage' => $report['coverage'],
            'issues' => $report['issues'],
            'generated_at' => $report['generated_at'],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget($this->validationCacheKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function runValidation(): array
    {
        $issues = collect()
            ->merge($this->moduleCompletenessValidator->validate())
            ->merge($this->metadataValidator->validate())
            ->merge($this->linkValidator->validate())
            ->merge($this->relatedDocumentValidator->validate())
            ->merge($this->releaseNotesValidator->validate())
            ->values();

        $errors = $issues->filter(fn (ValidationIssue $issue): bool => $issue->type === 'error');
        $warnings = $issues->filter(fn (ValidationIssue $issue): bool => $issue->type === 'warning');

        $statistics = [
            'total_modules' => $this->documentation->getConfiguredEnabledModuleKeys()->count(),
            'total_documents' => $this->documentation->discoverAllDocuments()->count(),
            'missing_documents' => $errors->where('code', 'missing_document')->count(),
            'broken_links' => $errors->whereIn('code', ['broken_link', 'broken_route_mapping', 'missing_related_document', 'disabled_related_document'])->count(),
            'invalid_metadata' => $issues->whereIn('code', ['missing_metadata', 'invalid_metadata'])->count(),
            'invalid_anchors' => $issues->where('code', 'invalid_anchor')->count(),
            'warnings' => $warnings->count(),
            'errors' => $errors->count(),
        ];

        return [
            'status' => $this->resolveHealthStatus($errors->count(), $warnings->count()),
            'statistics' => $statistics,
            'coverage' => $this->buildCoverage($issues),
            'issues' => $issues->map(fn (ValidationIssue $issue): array => $issue->toArray())->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(): array
    {
        return [
            'status' => 'healthy',
            'statistics' => [
                'total_modules' => 0,
                'total_documents' => 0,
                'missing_documents' => 0,
                'broken_links' => 0,
                'invalid_metadata' => 0,
                'invalid_anchors' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'coverage' => [],
            'issues' => [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, ValidationIssue>  $issues
     * @return array<int, array<string, mixed>>
     */
    private function buildCoverage(Collection $issues): array
    {
        $requiredDocuments = config('documentation.validation.required_documents', []);
        $exemptions = collect(config('documentation.validation.module_exemptions', []));
        $discoveredByModule = $this->documentation->discoverAllDocuments()->groupBy('module');

        return $this->documentation->getConfiguredEnabledModuleKeys()
            ->reject(fn (string $module): bool => $exemptions->contains($module))
            ->map(function (string $module) use ($requiredDocuments, $discoveredByModule, $issues): array {
                $moduleIssues = $issues->filter(fn (ValidationIssue $issue): bool => $issue->module === $module);
                $missing = $moduleIssues->where('code', 'missing_document')->count();
                $presentSections = 0;

                foreach ($requiredDocuments as $label => $relativePath) {
                    if (! is_string($relativePath)) {
                        continue;
                    }

                    $target = $module.'/'.$relativePath;
                    if (str_ends_with($relativePath, '.md')) {
                        if ($discoveredByModule->get($module, collect())->contains(fn (array $doc): bool => $doc['slug'] === $target)) {
                            $presentSections++;
                        }

                        continue;
                    }

                    if ($discoveredByModule->get($module, collect())->contains(fn (array $doc): bool => str_starts_with((string) $doc['slug'], $target.'/'))) {
                        $presentSections++;
                    }
                }

                $requiredCount = count($requiredDocuments);

                return [
                    'module' => $module,
                    'module_name' => config("documentation.modules.{$module}.name", $module),
                    'documents' => $discoveredByModule->get($module, collect())->count(),
                    'required_sections' => $requiredCount,
                    'present_sections' => $presentSections,
                    'missing_sections' => $missing,
                    'errors' => $moduleIssues->where('type', 'error')->count(),
                    'warnings' => $moduleIssues->where('type', 'warning')->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveHealthStatus(int $errorCount, int $warningCount): string
    {
        if ($errorCount > 0) {
            return 'failed';
        }

        if ($warningCount > 0) {
            return 'warning';
        }

        return 'healthy';
    }

    private function validationCacheKey(): string
    {
        $configHash = md5((string) json_encode([
            'validation' => config('documentation.validation'),
            'document_metadata' => config('documentation.document_metadata'),
            'route_mappings' => config('documentation.route_mappings'),
            'modules' => config('documentation.modules'),
        ]));

        return 'knowledge-center:validation:'.$this->documentation->getDocumentationFingerprint().':'.$configHash;
    }

    private function cacheTtl(): int
    {
        return max(1, (int) config('documentation.validation.cache_ttl', config('documentation.cache_ttl', 3600)));
    }
}
