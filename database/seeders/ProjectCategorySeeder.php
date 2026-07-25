<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\ProjectDefaultsService;
use Illuminate\Database\Seeder;

class ProjectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ProjectDefaultsService::class);

        Organization::query()->each(function (Organization $organization) use ($service) {
            $service->seedCategories($organization);
        });
    }
}
