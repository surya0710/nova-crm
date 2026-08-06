<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\HiringDecision;
use App\Models\InterviewRound;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\OfferLetter;
use App\Models\OfferTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\RecruitmentAnalyticsService;
use App\Services\Recruitment\RecruitmentDashboardService;
use App\Services\Recruitment\RecruitmentKpiService;
use App\Services\Recruitment\RecruitmentTrendService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_kpis_calculate_counts_and_rates(): void
    {
        [$organization, $hr, $department, $opening, $recruiter] = $this->analyticsScenario();

        $candidate = Candidate::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'linkedin',
            'created_at' => now(),
        ]);

        $application = JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'stage' => 'hired',
            'status' => 'closed',
            'source' => 'linkedin',
            'assigned_recruiter_id' => $recruiter->id,
            'applied_date' => now()->subDays(10)->toDateString(),
        ]);

        OfferLetter::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_application_id' => $application->id,
            'offer_template_id' => OfferTemplate::factory()->create(['organization_id' => $organization->id])->id,
            'status' => 'accepted',
            'sent_at' => now()->subDays(3),
            'accepted_at' => now()->subDay(),
        ]);

        HiringDecision::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'recommendation' => 'hire',
            'decision_date' => now()->toDateString(),
            'decision_by' => $hr->id,
        ]);

        InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => \App\Models\InterviewStage::factory()->create([
                'organization_id' => $organization->id,
            ])->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $kpis = app(RecruitmentKpiService::class)->executiveKpis(['period' => 'month'], $hr);

        $this->assertSame(1, $kpis['open_positions']);
        $this->assertSame(1, $kpis['offers_accepted']);
        $this->assertSame(1, $kpis['applications_this_period']);
        $this->assertSame(1, $kpis['hires_this_period']);
        $this->assertSame(100.0, $kpis['hiring_rate']);
        $this->assertSame(10.0, $kpis['time_to_hire']);
        $this->assertSame(1, $kpis['active_recruiters']);
    }

    public function test_funnel_and_source_analytics(): void
    {
        [$organization, $hr, $department, $opening] = $this->analyticsScenario();

        foreach ([['applied', 'linkedin'], ['interview', 'referral'], ['hired', 'agency']] as [$stage, $source]) {
            $candidate = Candidate::factory()->create(['organization_id' => $organization->id, 'source' => $source]);
            JobApplication::factory()->create([
                'organization_id' => $organization->id,
                'candidate_id' => $candidate->id,
                'job_opening_id' => $opening->id,
                'stage' => $stage,
                'status' => $stage === 'hired' ? 'closed' : 'active',
                'source' => $source,
                'applied_date' => now()->toDateString(),
            ]);
        }

        $hiredApp = JobApplication::query()->where('stage', 'hired')->firstOrFail();
        HiringDecision::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $hiredApp->id,
            'recommendation' => 'hire',
            'decision_date' => now()->toDateString(),
            'decision_by' => $hr->id,
            'onboarding_recommended' => true,
        ]);

        $funnel = app(RecruitmentAnalyticsService::class)->funnel(['period' => 'month'], $hr);
        $this->assertSame(3, $funnel['stages'][0]['count']);
        $this->assertSame(1, collect($funnel['stages'])->firstWhere('stage', 'hired')['count']);
        $this->assertSame(1, collect($funnel['stages'])->firstWhere('stage', 'onboarding')['count']);

        $sources = app(RecruitmentAnalyticsService::class)->sourceEffectiveness(['period' => 'month'], $hr);
        $this->assertNotEmpty($sources);
        $linkedin = collect($sources)->firstWhere('source', 'linkedin');
        $this->assertSame(1, $linkedin['applications']);
    }

    public function test_recruiter_and_department_metrics(): void
    {
        [$organization, $hr, $department, $opening, $recruiter] = $this->analyticsScenario();

        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $application = JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'assigned_recruiter_id' => $recruiter->id,
            'stage' => 'hired',
            'applied_date' => now()->subDays(5)->toDateString(),
        ]);

        HiringDecision::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'recommendation' => 'hire',
            'decision_date' => now()->toDateString(),
            'decision_by' => $hr->id,
        ]);

        $recruiters = app(RecruitmentAnalyticsService::class)->recruiterPerformance([
            'period' => 'month',
            'leaderboard_period' => 'monthly',
        ], $hr);

        $this->assertCount(1, $recruiters);
        $this->assertSame($recruiter->id, $recruiters[0]['recruiter_id']);
        $this->assertSame(1, $recruiters[0]['successful_hires']);

        $departments = app(RecruitmentAnalyticsService::class)->departmentAnalytics(['period' => 'month'], $hr);
        $this->assertSame(1, $departments['hiring_by_department'][0]['hires']);
        $this->assertNotEmpty($departments['vacancy_aging']);
    }

    public function test_trend_calculations_return_series(): void
    {
        [$organization, $hr, $department, $opening] = $this->analyticsScenario();

        $candidate = Candidate::factory()->create([
            'organization_id' => $organization->id,
            'created_at' => now(),
        ]);
        JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
            'applied_date' => now()->toDateString(),
        ]);

        $trends = app(RecruitmentTrendService::class)->trends(['period' => 'month'], $hr);

        $this->assertArrayHasKey('hiring_trends', $trends);
        $this->assertArrayHasKey('candidate_growth', $trends);
        $this->assertArrayHasKey('recruitment_volume', $trends);
        $this->assertGreaterThan(0, array_sum(array_column($trends['candidate_growth'], 'total')));
    }

    public function test_hiring_manager_dashboard_metrics(): void
    {
        [$organization, $hr, $department, $opening] = $this->analyticsScenario();

        JobRequisition::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'designation_id' => $opening->designation_id,
            'status' => 'pending_approval',
        ]);

        $metrics = app(RecruitmentDashboardService::class)->hiringManagerMetrics(['period' => 'month'], $hr);

        $this->assertGreaterThanOrEqual(1, $metrics['open_requisitions']);
        $this->assertSame(1, $metrics['pending_approvals']);
    }

    /**
     * @return array{0: Organization, 1: User, 2: Department, 3: JobOpening, 4: User}
     */
    private function analyticsScenario(): array
    {
        $organization = Organization::factory()->create();
        app(TenantContext::class)->set($organization);

        $hr = User::factory()->create();
        $recruiter = User::factory()->create();
        $organization->addMember($hr, 'hr');
        $organization->addMember($recruiter, 'hr');

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
            'publish_date' => now()->subDays(20)->toDateString(),
        ]);

        return [$organization, $hr, $department, $opening, $recruiter];
    }
}
