<?php

namespace Tests\Feature;

use App\Events\FeedbackCampaignCreated;
use App\Events\FeedbackClosed;
use App\Events\FeedbackRequestSent;
use App\Events\FeedbackStarted;
use App\Events\FeedbackSubmitted;
use App\Models\Employee;
use App\Models\FeedbackCampaign;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackTemplate;
use App\Models\Organization;
use App\Models\PerformanceConfiguration;
use App\Models\PerformanceCycle;
use App\Models\PerformanceRatingScale;
use App\Models\Permission;
use App\Models\User;
use App\Services\Hrms\FeedbackService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HrmsFeedbackPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-21 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_feedback_tables_exist(): void
    {
        foreach ([
            'feedback_templates',
            'feedback_questions',
            'feedback_campaigns',
            'feedback_participants',
            'feedback_requests',
            'feedback_responses',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_feedback_permissions_are_seeded_for_roles(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();

        foreach (['performance.feedback.view', 'performance.feedback.manage', 'performance.feedback.submit'] as $slug) {
            $this->assertNotNull(Permission::query()->where('slug', $slug)->first(), "Missing permission: {$slug}");
            $this->assertTrue($hr->hasPermission($slug, $organization));
        }

        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $this->assertTrue($manager->hasPermission('performance.feedback.view', $organization));
        $this->assertTrue($manager->hasPermission('performance.feedback.submit', $organization));
        $this->assertFalse($manager->hasPermission('performance.feedback.manage', $organization));

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');
        $this->assertTrue($employee->hasPermission('performance.feedback.view', $organization));
        $this->assertTrue($employee->hasPermission('performance.feedback.submit', $organization));
        $this->assertFalse($employee->hasPermission('performance.feedback.manage', $organization));
    }

    public function test_campaign_crud_and_lifecycle(): void
    {
        Event::fake([FeedbackCampaignCreated::class, FeedbackClosed::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.performance.feedback.campaigns.store'), [
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Q3 360 Feedback',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-31',
            'is_anonymous' => true,
        ])->assertRedirect();

        $campaign = FeedbackCampaign::query()->firstOrFail();
        $this->assertSame('draft', $campaign->status);
        $this->assertTrue($campaign->is_anonymous);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback_campaign_created']);
        Event::assertDispatched(FeedbackCampaignCreated::class);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.feedback.campaigns.activate', $campaign))
            ->assertRedirect();

        $campaign->refresh();
        $this->assertSame('active', $campaign->status);

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.performance.feedback.campaigns.close', $campaign))
            ->assertRedirect();

        $campaign->refresh();
        $this->assertSame('closed', $campaign->status);
        $this->assertNotNull($campaign->summary);
        Event::assertDispatched(FeedbackClosed::class);
    }

    public function test_participant_assignment_and_request_generation(): void
    {
        Event::fake([FeedbackCampaignCreated::class, FeedbackRequestSent::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);
        $service = app(FeedbackService::class);

        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Peer Feedback',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
            'is_anonymous' => true,
        ], $hr);

        $service->activateCampaign($campaign, $hr);

        $participant = $service->addParticipant($campaign, [
            'subject_employee_id' => $ctx['subject']->id,
            'participant_employee_id' => $ctx['peer']->id,
            'participant_type' => 'peer',
        ], $hr);

        $this->assertSame('active', $participant->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback_participant_assigned']);

        $requests = $service->generateRequests($campaign, $hr);
        $this->assertCount(1, $requests);
        $this->assertSame('pending', $requests->first()->status);
        $this->assertTrue($requests->first()->is_anonymous);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback_request_generated']);
        Event::assertDispatched(FeedbackRequestSent::class);
    }

    public function test_anonymous_feedback_submission_workflow(): void
    {
        Event::fake([
            FeedbackCampaignCreated::class,
            FeedbackRequestSent::class,
            FeedbackStarted::class,
            FeedbackSubmitted::class,
        ]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);

        $peerUser = User::factory()->create();
        $organization->addMember($peerUser, 'employee');
        $ctx['peer']->update(['user_id' => $peerUser->id]);

        $service = app(FeedbackService::class);
        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Anonymous 360',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
            'is_anonymous' => true,
        ], $hr);

        $service->activateCampaign($campaign, $hr);
        $service->addParticipant($campaign, [
            'subject_employee_id' => $ctx['subject']->id,
            'participant_employee_id' => $ctx['peer']->id,
            'participant_type' => 'peer',
        ], $hr);

        $requests = $service->generateRequests($campaign, $hr);
        $feedbackRequest = $requests->first();

        $peerSession = ['current_organization_id' => $organization->id];
        $questions = $ctx['template']->questions;

        $this->actingAs($peerUser)->withSession($peerSession)
            ->post(route('hrms.performance.feedback.requests.start', $feedbackRequest))
            ->assertRedirect();

        $feedbackRequest->refresh();
        $this->assertSame('started', $feedbackRequest->status);
        Event::assertDispatched(FeedbackStarted::class);

        $responses = $questions->map(fn ($q) => [
            'feedback_question_id' => $q->id,
            'rating' => $q->isRatingQuestion() ? 4 : null,
            'text_response' => $q->question_type === 'text' ? 'Strong leadership and excellent collaboration skills.' : null,
        ])->all();

        $this->actingAs($peerUser)->withSession($peerSession)
            ->post(route('hrms.performance.feedback.requests.submit', $feedbackRequest), [
                'responses' => $responses,
            ])->assertRedirect();

        $feedbackRequest->refresh();
        $this->assertSame('submitted', $feedbackRequest->status);
        $this->assertNotNull($feedbackRequest->submitted_at);
        $this->assertCount($questions->count(), $feedbackRequest->responses);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback_submitted']);
        Event::assertDispatched(FeedbackSubmitted::class);

        $this->actingAs($peerUser)->withSession($peerSession)
            ->post(route('hrms.performance.feedback.requests.submit', $feedbackRequest), [
                'responses' => $responses,
            ])->assertSessionHasErrors();
    }

    public function test_aggregation_and_summary_generation(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);
        $service = app(FeedbackService::class);

        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Aggregation Test',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
            'is_anonymous' => true,
        ], $hr);

        $service->activateCampaign($campaign, $hr);
        $service->addParticipant($campaign, [
            'subject_employee_id' => $ctx['subject']->id,
            'participant_employee_id' => $ctx['peer']->id,
            'participant_type' => 'peer',
        ], $hr);

        $requests = $service->generateRequests($campaign, $hr);
        $feedbackRequest = $requests->first();

        $responses = $ctx['template']->questions->map(fn ($q) => [
            'feedback_question_id' => $q->id,
            'rating' => $q->isRatingQuestion() ? 4 : null,
            'text_response' => $q->question_type === 'text' ? 'Great communicator with strong leadership.' : null,
        ])->all();

        $service->submitFeedback($feedbackRequest, $responses, $hr);

        $aggregation = $service->aggregateFeedback($campaign, $ctx['subject']->id);
        $this->assertNotNull($aggregation['overall_average']);
        $this->assertGreaterThan(0, $aggregation['total_responses']);
        $this->assertNotEmpty($aggregation['by_participant_type']);

        $summary = $service->generateSummary($campaign, $hr);
        $this->assertArrayHasKey('participation_rate', $summary);
        $this->assertArrayHasKey('competency_breakdown', $summary);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback_summary_generated']);
    }

    public function test_rbac_denies_unauthorized_access(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);
        $service = app(FeedbackService::class);

        $outsider = User::factory()->create();
        $organization->addMember($outsider, 'sales-executive');

        $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'RBAC Test',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
        ], $hr);

        $this->actingAs($outsider)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.performance.feedback.campaigns.index'))
            ->assertForbidden();

        $this->actingAs($outsider)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.performance.feedback.campaigns.store'), [
                'performance_cycle_id' => $ctx['cycle']->id,
                'feedback_template_id' => $ctx['template']->id,
                'name' => 'Should Fail',
                'start_date' => '2026-07-01',
                'due_date' => '2026-08-15',
            ])->assertForbidden();
    }

    public function test_tenant_isolation(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB, $hrB] = $this->organizationWithHrUser();

        app(TenantContext::class)->set($orgA);
        $ctxA = $this->seedFeedbackContext($orgA);
        $service = app(FeedbackService::class);
        $campaignA = $service->createCampaign([
            'performance_cycle_id' => $ctxA['cycle']->id,
            'feedback_template_id' => $ctxA['template']->id,
            'name' => 'Org A Campaign',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
        ], $hrA);

        app(TenantContext::class)->set($orgB);
        $ctxB = $this->seedFeedbackContext($orgB);
        $campaignB = $service->createCampaign([
            'performance_cycle_id' => $ctxB['cycle']->id,
            'feedback_template_id' => $ctxB['template']->id,
            'name' => 'Org B Campaign',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
        ], $hrB);

        app(TenantContext::class)->set($orgA);
        $this->assertSame(1, FeedbackCampaign::query()->count());

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.performance.feedback.campaigns.show', $campaignB))
            ->assertNotFound();
    }

    public function test_anonymous_required_org_config(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);
        PerformanceConfiguration::query()->where('organization_id', $organization->id)->update([
            'feedback_anonymous_required' => true,
        ]);

        $service = app(FeedbackService::class);
        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Forced Anonymous',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
            'is_anonymous' => false,
        ], $hr);

        $this->assertTrue($campaign->is_anonymous);
    }

    public function test_responses_are_immutable_after_submission(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);

        $ctx = $this->seedFeedbackContext($organization);
        $service = app(FeedbackService::class);

        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'Immutable Test',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
        ], $hr);

        $service->activateCampaign($campaign, $hr);
        $service->addParticipant($campaign, [
            'subject_employee_id' => $ctx['subject']->id,
            'participant_employee_id' => $ctx['peer']->id,
            'participant_type' => 'peer',
        ], $hr);

        $feedbackRequest = $service->generateRequests($campaign, $hr)->first();
        $responses = $ctx['template']->questions->map(fn ($q) => [
            'feedback_question_id' => $q->id,
            'rating' => $q->isRatingQuestion() ? 3 : null,
            'text_response' => $q->question_type === 'text' ? 'Initial response' : null,
        ])->all();

        $service->submitFeedback($feedbackRequest, $responses, $hr);

        $this->expectException(ValidationException::class);
        $service->submitFeedback($feedbackRequest, $responses, $hr);
    }

    public function test_feedback_dashboard_and_my_feedback_pages(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        app(TenantContext::class)->set($organization);
        $session = ['current_organization_id' => $organization->id];

        $ctx = $this->seedFeedbackContext($organization);
        $peerUser = User::factory()->create();
        $organization->addMember($peerUser, 'employee');
        $ctx['peer']->update(['user_id' => $peerUser->id]);

        $service = app(FeedbackService::class);
        $campaign = $service->createCampaign([
            'performance_cycle_id' => $ctx['cycle']->id,
            'feedback_template_id' => $ctx['template']->id,
            'name' => 'UI Test Campaign',
            'start_date' => '2026-07-01',
            'due_date' => '2026-08-15',
        ], $hr);

        $service->activateCampaign($campaign, $hr);
        $service->addParticipant($campaign, [
            'subject_employee_id' => $ctx['subject']->id,
            'participant_employee_id' => $ctx['peer']->id,
            'participant_type' => 'peer',
        ], $hr);
        $service->generateRequests($campaign, $hr);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.performance.feedback.index'))
            ->assertOk()
            ->assertSee('UI Test Campaign');

        $this->actingAs($peerUser)->withSession($session)
            ->get(route('hrms.performance.feedback.my-feedback'))
            ->assertOk()
            ->assertSee($ctx['subject']->first_name);
    }

    /**
     * @return array{
     *     cycle: PerformanceCycle,
     *     template: FeedbackTemplate,
     *     subject: Employee,
     *     peer: Employee,
     *     manager: Employee
     * }
     */
    private function seedFeedbackContext(Organization $organization): array
    {
        $scale = PerformanceRatingScale::factory()->create([
            'organization_id' => $organization->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        PerformanceConfiguration::factory()->create([
            'organization_id' => $organization->id,
            'rating_scale_id' => $scale->id,
            'feedback_anonymous_enabled' => true,
            'feedback_anonymous_required' => false,
        ]);

        $cycle = PerformanceCycle::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'active',
            'name' => 'FY 2026',
        ]);

        $template = FeedbackTemplate::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Standard 360 Form',
        ]);

        FeedbackQuestion::factory()->create([
            'organization_id' => $organization->id,
            'feedback_template_id' => $template->id,
            'question_type' => 'rating',
            'question_text' => 'Overall performance rating',
            'scale_min' => 1,
            'scale_max' => 5,
            'sort_order' => 0,
        ]);

        FeedbackQuestion::factory()->create([
            'organization_id' => $organization->id,
            'feedback_template_id' => $template->id,
            'question_type' => 'text',
            'question_text' => 'What are this person\'s key strengths?',
            'sort_order' => 1,
        ]);

        FeedbackQuestion::factory()->create([
            'organization_id' => $organization->id,
            'feedback_template_id' => $template->id,
            'question_type' => 'text',
            'question_text' => 'What areas could this person improve?',
            'sort_order' => 2,
        ]);

        $manager = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Manager',
        ]);

        $subject = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Subject',
            'reporting_manager_id' => $manager->id,
        ]);

        $peer = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Peer',
        ]);

        $template->load('questions');

        return compact('cycle', 'template', 'subject', 'peer', 'manager');
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
