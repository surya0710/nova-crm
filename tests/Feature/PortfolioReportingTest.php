<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\PortfolioReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_generate_portfolio_report(): void
    {
        Notification::fake();

        [$user, $organization] = $this->setupUserWithOrg();

        $portfolio = Portfolio::factory()->create([
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('portfolio-reports.store'), [
                'report_type' => 'portfolio',
                'format' => 'csv',
                'portfolio_id' => $portfolio->id,
            ])
            ->assertRedirect(route('portfolio-reports.index'));

        $report = PortfolioReport::query()
            ->where('organization_id', $organization->id)
            ->where('report_type', 'portfolio')
            ->firstOrFail();

        $this->assertSame('csv', $report->format);
        $this->assertNotNull($report->storage_path);
        Storage::assertExists($report->storage_path);
    }

    public function test_report_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('portfolio-reports.index'))
            ->assertOk();
    }
}
