<?php

namespace App\Services\Documentation\Validators;

use App\Services\Documentation\ValidationIssue;
use App\Services\DocumentationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReleaseNotesValidator
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
        $exemptions = collect(config('documentation.validation.module_exemptions', []));
        $paths = config('documentation.validation.release.paths', ['release-notes/overview.md']);
        $root = $this->documentation->getDocumentationRootPath();

        foreach ($this->documentation->getConfiguredEnabledModuleKeys() as $module) {
            if ($exemptions->contains($module)) {
                continue;
            }

            $releasePath = $this->resolveReleaseNotesPath($module, $paths, $root);
            if ($releasePath === null) {
                $issues[] = new ValidationIssue(
                    type: 'error',
                    code: 'missing_release_notes',
                    message: sprintf('Module [%s] is missing release notes.', $module),
                    module: $module,
                    slug: $module.'/release-notes',
                );

                continue;
            }

            $content = File::get($releasePath);
            $issues = array_merge($issues, $this->validateReleaseNotesContent($module, $content));
        }

        return $issues;
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function resolveReleaseNotesPath(string $module, array $paths, string $root): ?string
    {
        foreach ($paths as $relativePath) {
            if (! is_string($relativePath) || $relativePath === '') {
                continue;
            }

            $candidate = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $module.'/'.$relativePath);
            if (File::isFile($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, ValidationIssue>
     */
    private function validateReleaseNotesContent(string $module, string $content): array
    {
        $issues = [];
        $sectionHeadings = config('documentation.validation.release.current_section_headings', ['Version', 'Current Release']);
        $versionPattern = (string) config('documentation.validation.release.version_pattern', '/v?\d+\.\d+\.\d+/');
        $requireEntries = config('documentation.validation.release.require_entries', true) !== false;

        $hasSection = false;
        foreach ($sectionHeadings as $heading) {
            if (is_string($heading) && preg_match('/^##\s+'.preg_quote($heading, '/').'\s*$/mi', $content)) {
                $hasSection = true;
                break;
            }
        }

        if (! $hasSection) {
            $issues[] = new ValidationIssue(
                type: 'error',
                code: 'invalid_release_notes',
                message: sprintf('Module [%s] release notes are missing a current release section.', $module),
                module: $module,
                slug: $module.'/release-notes',
            );
        }

        if ($versionPattern !== '' && ! preg_match($versionPattern, $content)) {
            $issues[] = new ValidationIssue(
                type: 'warning',
                code: 'invalid_release_notes',
                message: sprintf('Module [%s] release notes do not contain a valid version identifier.', $module),
                module: $module,
                slug: $module.'/release-notes',
            );
        }

        if ($requireEntries && ! preg_match('/^[\-*+]\s+.+/m', $content)) {
            $issues[] = new ValidationIssue(
                type: 'warning',
                code: 'invalid_release_notes',
                message: sprintf('Module [%s] release notes do not contain release entries.', $module),
                module: $module,
                slug: $module.'/release-notes',
            );
        }

        return $issues;
    }
}
