<?php

namespace App\Services\Documentation\Validators;

use App\Services\Documentation\ValidationIssue;
use App\Services\DocumentationService;

class RelatedDocumentValidator
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
        /** @var array<string, array<string, mixed>> $metadataConfig */
        $metadataConfig = config('documentation.document_metadata', []);

        foreach ($metadataConfig as $slug => $configured) {
            if (! is_string($slug) || ! is_array($configured)) {
                continue;
            }

            $related = $configured['related'] ?? [];
            if (! is_array($related)) {
                continue;
            }

            $seen = [];
            $module = explode('/', $slug)[0] ?? null;

            foreach ($related as $relatedSlug) {
                if (! is_string($relatedSlug) || $relatedSlug === '') {
                    $issues[] = new ValidationIssue(
                        type: 'warning',
                        code: 'invalid_related_document',
                        message: sprintf('Document [%s] contains an invalid related document entry.', $slug),
                        module: is_string($module) ? $module : null,
                        slug: $slug,
                    );

                    continue;
                }

                if ($relatedSlug === $slug) {
                    $issues[] = new ValidationIssue(
                        type: 'warning',
                        code: 'circular_related_document',
                        message: sprintf('Document [%s] references itself as a related document.', $slug),
                        module: is_string($module) ? $module : null,
                        slug: $slug,
                        context: ['related' => $relatedSlug],
                    );
                }

                if (in_array($relatedSlug, $seen, true)) {
                    $issues[] = new ValidationIssue(
                        type: 'warning',
                        code: 'duplicate_related_document',
                        message: sprintf('Document [%s] lists duplicate related document [%s].', $slug, $relatedSlug),
                        module: is_string($module) ? $module : null,
                        slug: $slug,
                        context: ['related' => $relatedSlug],
                    );
                }

                $seen[] = $relatedSlug;

                $document = $this->documentation->findDiscoveredDocument($relatedSlug);
                if ($document === null) {
                    $issues[] = new ValidationIssue(
                        type: 'error',
                        code: 'missing_related_document',
                        message: sprintf('Document [%s] references missing related document [%s].', $slug, $relatedSlug),
                        module: is_string($module) ? $module : null,
                        slug: $slug,
                        context: ['related' => $relatedSlug],
                    );

                    continue;
                }

                $relatedModule = (string) $document['module'];
                if (! $this->documentation->isDocumentationModuleEnabled($relatedModule)) {
                    $issues[] = new ValidationIssue(
                        type: 'error',
                        code: 'disabled_related_document',
                        message: sprintf('Document [%s] references related document [%s] in a disabled module.', $slug, $relatedSlug),
                        module: is_string($module) ? $module : null,
                        slug: $slug,
                        context: ['related' => $relatedSlug, 'module' => $relatedModule],
                    );
                }
            }
        }

        return $issues;
    }
}
