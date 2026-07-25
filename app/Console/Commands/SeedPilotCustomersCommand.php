<?php

namespace App\Console\Commands;

use Database\Seeders\PilotCustomerSeeder;
use Illuminate\Console\Command;

class SeedPilotCustomersCommand extends Command
{
    protected $signature = 'pilot:seed {--force : Reserved; seeder skips orgs that are already fully seeded}';

    protected $description = 'Seed five Program 15.8 pilot organizations with distinct module licensing mixes';

    public function handle(): int
    {
        if ($this->option('force')) {
            $this->warn('--force is reserved; the seeder skips pilots that already have sample employees.');
        }

        $this->call('db:seed', ['--class' => PilotCustomerSeeder::class]);

        return self::SUCCESS;
    }
}
