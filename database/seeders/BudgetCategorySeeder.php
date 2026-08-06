<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\BudgetService;
use Illuminate\Database\Seeder;

class BudgetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $budgetService = app(BudgetService::class);

        Organization::query()->each(function (Organization $organization) use ($budgetService) {
            $budgetService->seedDefaultCategories($organization);
        });
    }
}
