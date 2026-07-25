<?php

namespace App\Console\Commands;

use Database\Seeders\PresentationDemoSeeder;
use Illuminate\Console\Command;

class SeedPresentationDemoCommand extends Command
{
    protected $signature = 'demo:seed-presentation {--force : Re-run even if demo data already exists}';

    protected $description = 'Seed Nova Enterprises presentation demo data for screenshots';

    public function handle(): int
    {
        if ($this->option('force')) {
            $this->warn('--force is reserved for future use; the seeder skips when fully seeded.');
        }

        $this->call('db:seed', ['--class' => PresentationDemoSeeder::class]);

        return self::SUCCESS;
    }
}
