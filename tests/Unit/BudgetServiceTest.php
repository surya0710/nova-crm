<?php

namespace Tests\Unit;

use App\Events\ProjectBudgetUpdated;
use App\Models\BudgetCategory;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\ProjectService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setupOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
    }

    protected function createProject(Organization $organization, User $actor): Project
    {
        return app(ProjectService::class)->create([
            'organization_id' => $organization->id,
            'name' => 'Budget Project',
            'owner_id' => $actor->id,
            'manager_id' => $actor->id,
            'priority' => 'medium',
        ], $actor);
    }

    public function test_seed_default_categories(): void
    {
        [, $organization] = $this->setupOrg();
        $service = app(BudgetService::class);

        $service->seedDefaultCategories($organization);

        $expected = config('projects.default_budget_categories', BudgetService::DEFAULT_CATEGORIES);

        $this->assertSame(
            count($expected),
            BudgetCategory::query()->where('organization_id', $organization->id)->where('is_system', true)->count()
        );
        $this->assertDatabaseHas('budget_categories', [
            'organization_id' => $organization->id,
            'slug' => $expected[0]['slug'] ?? 'labor',
            'is_system' => true,
        ]);
    }

    public function test_create_budget_with_items_recalculates_totals(): void
    {
        Notification::fake();
        Event::fake([ProjectBudgetUpdated::class]);

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);

        $budget = app(BudgetService::class)->create($project, [
            'name' => 'Primary',
            'currency' => 'USD',
            'status' => 'draft',
        ], [
            ['name' => 'Dev Labor', 'category_slug' => 'labor', 'planned' => 1000, 'actual' => 200, 'forecast' => 1100],
            ['name' => 'Licenses', 'category_slug' => 'software', 'planned' => 500, 'actual' => 500, 'forecast' => 500],
        ], $user);

        $this->assertInstanceOf(ProjectBudget::class, $budget);
        $this->assertEquals(1500.0, (float) $budget->planned_total);
        $this->assertEquals(700.0, (float) $budget->actual_total);
        $this->assertEquals(1600.0, (float) $budget->forecast_total);
        $this->assertCount(2, $budget->items);
        Event::assertDispatched(ProjectBudgetUpdated::class);
    }

    public function test_update_budget_syncs_items(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupOrg();
        $project = $this->createProject($organization, $user);
        $service = app(BudgetService::class);

        $budget = $service->create($project, ['name' => 'Primary'], [
            ['name' => 'Item A', 'category_slug' => 'other', 'planned' => 100, 'actual' => 0, 'forecast' => 100],
        ], $user);

        Event::fake([ProjectBudgetUpdated::class]);

        $updated = $service->update($budget, ['status' => 'approved'], [
            ['name' => 'Item A', 'category_slug' => 'other', 'planned' => 200, 'actual' => 50, 'forecast' => 180],
            ['name' => 'Item B', 'category_slug' => 'travel', 'planned' => 80, 'actual' => 0, 'forecast' => 80],
        ], $user);

        $this->assertSame('approved', $updated->status);
        $this->assertEquals(280.0, (float) $updated->planned_total);
        $this->assertCount(2, $updated->items);
        Event::assertDispatched(ProjectBudgetUpdated::class);
    }
}
