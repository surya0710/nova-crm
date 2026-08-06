<?php

namespace App\Services\Documentation\Validators;

use App\Services\Documentation\ValidationIssue;
use App\Services\DocumentationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleCompletenessValidator
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
        $requiredDocuments = config('documentation.validation.required_documents', []);
        $exemptions = collect(config('documentation.validation.module_exemptions', []));
        $root = $this->documentation->getDocumentationRootPath();
        $discoveredSlugs = $this->documentation->discoverAllDocuments()->pluck('slug');

        foreach ($this->documentation->getConfiguredEnabledModuleKeys() as $module) {
            if ($exemptions->contains($module)) {
                continue;
            }

            foreach ($requiredDocuments as $label => $relativePath) {
                if (! is_string($relativePath) || $relativePath === '') {
                    continue;
                }

                $target = $module.'/'.$relativePath;

                if (Str::endsWith($relativePath, '.md')) {
                    $slug = Str::of($target)->beforeLast('.md')->toString();
                    if (! $discoveredSlugs->contains($slug)) {
                        $issues[] = new ValidationIssue(
                            type: 'error',
                            code: 'missing_document',
                            message: sprintf('Module [%s] is missing required document: %s.', $module, $label),
                            module: $module,
                            slug: $target,
                            context: ['label' => $label, 'expected' => $target],
                        );
                    }

                    continue;
                }

                $directory = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target);
                $hasMarkdown = File::isDirectory($directory)
                    && collect(File::allFiles($directory))
                        ->contains(fn ($file): bool => $file->getExtension() === 'md');

                if (! $hasMarkdown) {
                    $issues[] = new ValidationIssue(
                        type: 'error',
                        code: 'missing_document',
                        message: sprintf('Module [%s] is missing required document section: %s.', $module, $label),
                        module: $module,
                        slug: $target,
                        context: ['label' => $label, 'expected_directory' => $target],
                    );
                }
            }
        }

        return $issues;
    }
}
