<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\TaskDefaultsService;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TaskDefaultsService::class);

        Organization::query()->each(function (Organization $organization) use ($service) {
            $service->seedStatuses($organization);
        });
    }
}
