<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\RecruitmentSavedReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmsRecruitmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_kpis_for_hr(): void
    {
        [$organization, $hr] = $this->analyticsScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.dashboard'))
            ->assertOk()
            ->assertSee(__('Open Positions'));
    }

    public function test_analytics_filters_and_sections(): void
    {
        [$organization, $hr, $department, $opening] = $this->analyticsScenario();
        $session = ['current_organization_id' => $organization->id];

        Candidate::factory()->create(['organization_id' => $organization->id]);
        JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'job_opening_id' => $opening->id,
            'candidate_id' => Candidate::factory()->create(['organization_id' => $organization->id])->id,
            'applied_date' => now()->toDateString(),
            'source' => 'linkedin',
        ]);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.analytics', ['period' => 'month', 'section' => 'sources']))
            ->assertOk()
            ->assertSee(__('Sources'));

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.executive', ['period' => 'week']))
            ->assertOk();
    }

    public function test_reports_and_exports_permissions(): void
    {
        [$organization, $hr] = $this->analyticsScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.reports.index', ['report_type' => 'recruitment_summary']))
            ->assertOk()
            ->assertSee(__('Recruitment Summary'));

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.exports.index'))
            ->assertOk();

        $response = $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.recruitment.exports.download'), [
                'report_type' => 'source',
                'format' => 'csv',
                'period' => 'month',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'recruitment_report_exported']);
    }

    public function test_saved_reports_crud_share_and_audit(): void
    {
        [$organization, $hr] = $this->analyticsScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.recruitment.saved-reports.store'), [
            'report_name' => 'Monthly Pipeline',
            'report_type' => 'pipeline',
            'period' => 'month',
            'is_shared' => '1',
        ])->assertRedirect(route('hrms.recruitment.saved-reports.index'));

        $report = RecruitmentSavedReport::query()->firstOrFail();
        $this->assertTrue($report->is_shared);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recruitment_report_created']);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.recruitment.saved-reports.share', $report))
            ->assertRedirect();

        $this->assertFalse($report->fresh()->is_shared);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recruitment_report_shared']);

        $this->actingAs($hr)->withSession($session)
            ->delete(route('hrms.recruitment.saved-reports.destroy', $report))
            ->assertRedirect(route('hrms.recruitment.saved-reports.index'));

        $this->assertDatabaseMissing('recruitment_saved_reports', ['id' => $report->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recruitment_report_deleted']);
    }

    public function test_unauthorized_user_cannot_access_analytics(): void
    {
        [$organization] = $this->analyticsScenario();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($employee)->withSession($session)
            ->get(route('hrms.recruitment.analytics'))
            ->assertForbidden();

        $this->actingAs($employee)->withSession($session)
            ->get(route('hrms.recruitment.reports.index'))
            ->assertForbidden();

        $this->actingAs($employee)->withSession($session)
            ->get(route('hrms.recruitment.exports.index'))
            ->assertForbidden();
    }

    public function test_tenant_isolation_for_saved_reports(): void
    {
        [$organization, $hr] = $this->analyticsScenario();
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create();
        $otherOrg->addMember($otherUser, 'hr');

        $foreign = RecruitmentSavedReport::factory()->create([
            'organization_id' => $otherOrg->id,
            'user_id' => $otherUser->id,
            'report_name' => 'Foreign Report',
        ]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.saved-reports.show', $foreign))
            ->assertForbidden();
    }

    public function test_pdf_export_is_rejected_as_placeholder(): void
    {
        [$organization, $hr] = $this->analyticsScenario();

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.recruitment.exports.download'), [
                'report_type' => 'recruitment_summary',
                'format' => 'pdf',
                'period' => 'month',
            ])
            ->assertSessionHasErrors('format');
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: JobOpening}
     */
    private function analyticsScenario(): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        $department = Department::factory()->create(['organization_id' => $organization->id]);
        $designation = Designation::factory()->create(['organization_id' => $organization->id]);
        $requisition = JobRequisition::factory()->approved()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
        $opening = JobOpening::factory()->published()->create([
            'organization_id' => $organization->id,
            'job_requisition_id' => $requisition->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        return [$organization, $hr, $department, $opening];
    }
}
