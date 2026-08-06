<?php

namespace Database\Seeders;

use App\Services\Dashboard\QuickActionService;
use Illuminate\Database\Seeder;

class ProjectCollaborationQuickActionSeeder extends Seeder
{
    public function run(): void
    {
        app(QuickActionService::class)->seedSystemActions();
    }
}
