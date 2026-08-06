<?php

namespace App\Console\Commands;

use App\Services\DocumentationValidationService;
use Illuminate\Console\Command;

class DocsHealthCommand extends Command
{
    protected $signature = 'docs:health
        {--no-cache : Generate health report without reading cached results}';

    protected $description = 'Display documentation health statistics and coverage.';

    public function handle(DocumentationValidationService $validation): int
    {
        $report = $validation->health(useCache: ! $this->option('no-cache'));
        $statistics = $report['statistics'];

        $this->line('');
        $this->info('Documentation Health Report');
        $this->line(str_repeat('-', 40));
        $this->line(sprintf('Status: %s', strtoupper((string) $report['status'])));
        $this->line(sprintf('Generated: %s', $report['generated_at']));
        $this->line('');
        $this->line(sprintf('Total modules: %d', $statistics['total_modules']));
        $this->line(sprintf('Total documents: %d', $statistics['total_documents']));
        $this->line(sprintf('Missing documents: %d', $statistics['missing_documents']));
        $this->line(sprintf('Broken links: %d', $statistics['broken_links']));
        $this->line(sprintf('Invalid metadata: %d', $statistics['invalid_metadata']));
        $this->line(sprintf('Invalid anchors: %d', $statistics['invalid_anchors']));
        $this->line(sprintf('Warnings: %d', $statistics['warnings']));
        $this->line(sprintf('Errors: %d', $statistics['errors']));
        $this->line('');

        if ($report['coverage'] !== []) {
            $this->info('Module Coverage');
            $rows = collect($report['coverage'])
                ->map(fn (array $entry): array => [
                    $entry['module'],
                    $entry['documents'],
                    sprintf('%d/%d', $entry['present_sections'], $entry['required_sections']),
                    $entry['errors'],
                    $entry['warnings'],
                ])
                ->all();

            $this->table(
                ['Module', 'Documents', 'Sections', 'Errors', 'Warnings'],
                $rows
            );
        }

        return self::SUCCESS;
    }
}
