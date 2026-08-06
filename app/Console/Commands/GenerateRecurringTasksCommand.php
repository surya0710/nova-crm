<?php

namespace App\Console\Commands;

use App\Services\TaskGenerationService;
use Illuminate\Console\Command;

class GenerateRecurringTasksCommand extends Command
{
    protected $signature = 'projects:generate-recurring-tasks';

    protected $description = 'Generate due recurring project tasks from active recurrence schedules';

    public function handle(TaskGenerationService $generation): int
    {
        $created = $generation->generateDue();
        $count = count($created);

        $this->info("Generated {$count} recurring task".($count === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
