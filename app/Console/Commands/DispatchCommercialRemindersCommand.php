<?php

namespace App\Console\Commands;

use App\Services\CommercialAutomationService;
use Illuminate\Console\Command;

class DispatchCommercialRemindersCommand extends Command
{
    protected $signature = 'commercial:dispatch-reminders';

    protected $description = 'Send invoice due, overdue, and quotation expiry reminders';

    public function handle(CommercialAutomationService $automation): int
    {
        $counts = $automation->dispatchScheduledReminders();

        $this->info(sprintf(
            'Dispatched %d due, %d overdue, and %d quotation reminders.',
            $counts['due'],
            $counts['overdue'],
            $counts['quotations'],
        ));

        return self::SUCCESS;
    }
}
