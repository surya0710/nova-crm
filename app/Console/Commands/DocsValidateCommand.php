<?php

namespace App\Console\Commands;

use App\Services\DocumentationValidationService;
use Illuminate\Console\Command;

class DocsValidateCommand extends Command
{
    protected $signature = 'docs:validate
        {--no-cache : Run validation without reading cached results}';

    protected $description = 'Validate documentation completeness, metadata, links, and release notes.';

    public function handle(DocumentationValidationService $validation): int
    {
        $report = $validation->validate(useCache: ! $this->option('no-cache'));
        $statistics = $report['statistics'];
        $issues = collect($report['issues']);

        $errors = $issues->where('type', 'error');
        $warnings = $issues->where('type', 'warning');

        $this->line('');
        $this->info('Documentation Validation Summary');
        $this->line(str_repeat('-', 40));
        $this->line(sprintf('Status: %s', strtoupper((string) $report['status'])));
        $this->line(sprintf('Modules: %d', $statistics['total_modules']));
        $this->line(sprintf('Documents: %d', $statistics['total_documents']));
        $this->line(sprintf('Errors: %d', $statistics['errors']));
        $this->line(sprintf('Warnings: %d', $statistics['warnings']));
        $this->line('');

        if ($errors->isNotEmpty()) {
            $this->error('Errors');
            foreach ($errors as $issue) {
                $this->line('- '.$issue['message']);
            }
            $this->line('');
        }

        if ($warnings->isNotEmpty()) {
            $this->warn('Warnings');
            foreach ($warnings as $issue) {
                $this->line('- '.$issue['message']);
            }
            $this->line('');
        }

        if ($errors->isEmpty() && $warnings->isEmpty()) {
            $this->info('Documentation validation passed with no issues.');
        }

        return $errors->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
