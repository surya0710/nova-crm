<?php

namespace Database\Seeders;

use App\Services\Dashboard\QuickActionService;
use Illuminate\Database\Seeder;

class ResourceQuickActionSeeder extends Seeder
{
    public function run(): void
    {
        app(QuickActionService::class)->seedSystemActions();
    }
}
