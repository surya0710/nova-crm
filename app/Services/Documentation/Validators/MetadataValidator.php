<?php

namespace App\Services\Documentation\Validators;

use App\Services\Documentation\ValidationIssue;
use App\Services\DocumentationService;
use Illuminate\Support\Collection;

class MetadataValidator
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
        $required = config('documentation.validation.metadata_schema.required', ['title', 'module', 'category']);
        $optional = config('documentation.validation.metadata_schema.optional', ['keywords', 'icon', 'related', 'order']);

        foreach ($this->validationDocuments() as $document) {
            $slug = (string) $document['slug'];
            $metadata = $this->documentation->getDocumentMetadata($slug, $document);

            foreach ($required as $field) {
                if (! is_string($field)) {
                    continue;
                }

                $value = $metadata[$field] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    $issues[] = new ValidationIssue(
                        type: 'error',
                        code: 'missing_metadata',
                        message: sprintf('Document [%s] is missing required metadata field: %s.', $slug, $field),
                        module: $document['module'],
                        slug: $slug,
                        context: ['field' => $field],
                    );
                }
            }

            /** @var array<string, mixed> $configured */
            $configured = config('documentation.document_metadata.'.$slug, []);
            if (! is_array($configured)) {
                continue;
            }

            foreach ($optional as $field) {
                if (! is_string($field) || ! array_key_exists($field, $configured)) {
                    continue;
                }

                $value = $configured[$field];
                $issue = $this->validateOptionalField($slug, (string) $document['module'], $field, $value);
                if ($issue !== null) {
                    $issues[] = $issue;
                }
            }
        }

        return $issues;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function validationDocuments(): Collection
    {
        $modules = $this->documentation->getConfiguredEnabledModuleKeys();

        return $this->documentation->discoverAllDocuments()
            ->filter(fn (array $document): bool => $modules->contains($document['module']));
    }

    private function validateOptionalField(string $slug, string $module, string $field, mixed $value): ?ValidationIssue
    {
        return match ($field) {
            'keywords', 'related' => is_array($value)
                ? null
                : new ValidationIssue(
                    type: 'warning',
                    code: 'invalid_metadata',
                    message: sprintf('Document [%s] has invalid metadata: %s must be an array.', $slug, $field),
                    module: $module,
                    slug: $slug,
                    context: ['field' => $field],
                ),
            'icon', 'order' => (is_string($value) || is_int($value))
                ? null
                : new ValidationIssue(
                    type: 'warning',
                    code: 'invalid_metadata',
                    message: sprintf('Document [%s] has invalid metadata: %s has an unsupported type.', $slug, $field),
                    module: $module,
                    slug: $slug,
                    context: ['field' => $field],
                ),
            default => null,
        };
    }
}
