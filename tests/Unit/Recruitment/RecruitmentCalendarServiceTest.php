<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\InterviewRound;
use App\Models\InterviewStage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\RecruitmentCalendarEvent;
use App\Models\User;
use App\Services\Recruitment\RecruitmentCalendarService;
use App\Services\Recruitment\RecruitmentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_requires_scheduled_at(): void
    {
        [$organization, $user, $round] = $this->calendarScenario(scheduled: false);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'google_calendar', $user);

        $this->expectException(ValidationException::class);
        app(RecruitmentCalendarService::class)->syncInterviewEvent($round, $provider, $user);
    }

    public function test_sync_with_connected_google_calendar_stores_external_event_id(): void
    {
        [$organization, $user, $round] = $this->calendarScenario(scheduled: true);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'google_calendar', $user);

        $event = app(RecruitmentCalendarService::class)->syncInterviewEvent($round, $provider, $user);

        $this->assertNotNull($event);
        $this->assertSame('synced', $event->status);
        $this->assertNotNull($event->external_event_id);
        $this->assertStringStartsWith('gcal_', $event->external_event_id);
        $this->assertSame($round->id, $event->interview_round_id);
        $this->assertDatabaseHas('recruitment_calendar_events', [
            'id' => $event->id,
            'organization_id' => $organization->id,
            'external_event_id' => $event->external_event_id,
        ]);
    }

    public function test_cancel_event(): void
    {
        [$organization, $user, $round] = $this->calendarScenario(scheduled: true);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'google_calendar', $user);
        $service = app(RecruitmentCalendarService::class);

        $event = $service->syncInterviewEvent($round, $provider, $user);
        $this->assertNotNull($event?->external_event_id);

        $service->cancelInterviewEvent($round, $user);

        $this->assertSame('cancelled', RecruitmentCalendarEvent::query()->findOrFail($event->id)->status);
    }

    /**
     * @return array{0: Organization, 1: User, 2: InterviewRound}
     */
    private function calendarScenario(bool $scheduled): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

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
        $candidate = Candidate::factory()->create(['organization_id' => $organization->id]);
        $application = JobApplication::factory()->create([
            'organization_id' => $organization->id,
            'candidate_id' => $candidate->id,
            'job_opening_id' => $opening->id,
        ]);
        $stage = InterviewStage::factory()->create(['organization_id' => $organization->id]);

        $round = InterviewRound::factory()->create([
            'organization_id' => $organization->id,
            'job_application_id' => $application->id,
            'interview_stage_id' => $stage->id,
            'status' => $scheduled ? 'scheduled' : 'draft',
            'scheduled_at' => $scheduled ? now()->addDays(2) : null,
        ]);

        return [$organization, $user, $round];
    }
}
