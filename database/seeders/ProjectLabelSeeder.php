<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\ProjectLabelService;
use Illuminate\Database\Seeder;

class ProjectLabelSeeder extends Seeder
{
    public function run(): void
    {
        $labelService = app(ProjectLabelService::class);

        Organization::query()->each(function (Organization $organization) use ($labelService) {
            $labelService->seedDefaults($organization);
        });
    }
}
