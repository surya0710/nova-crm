<?php

namespace Tests\Unit\Recruitment;

use App\Models\Department;
use App\Models\Designation;
use App\Models\JobOpening;
use App\Models\JobRequisition;
use App\Models\Organization;
use App\Models\RecruitmentJobBoardListing;
use App\Models\User;
use App\Services\Recruitment\JobOpeningService;
use App\Services\Recruitment\RecruitmentJobBoardService;
use App\Services\Recruitment\RecruitmentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentJobBoardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_publish_non_published_opening(): void
    {
        [$organization, $user, $opening] = $this->openingScenario(published: false);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'linkedin_jobs', $user);

        $this->expectException(ValidationException::class);
        app(RecruitmentJobBoardService::class)->publishOpening($opening, $provider, $user);
    }

    public function test_publish_to_connected_linkedin_jobs_stores_external_job_id(): void
    {
        [$organization, $user, $opening] = $this->openingScenario(published: true);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'linkedin_jobs', $user);

        $listing = app(RecruitmentJobBoardService::class)->publishOpening($opening, $provider, $user);

        $this->assertSame('published', $listing->status);
        $this->assertNotNull($listing->external_job_id);
        $this->assertStringStartsWith('linkedin_jobs_', $listing->external_job_id);
        $this->assertDatabaseHas('recruitment_job_board_listings', [
            'id' => $listing->id,
            'job_opening_id' => $opening->id,
            'organization_id' => $organization->id,
            'external_job_id' => $listing->external_job_id,
        ]);
    }

    public function test_closing_opening_closes_listings_via_job_opening_service(): void
    {
        [$organization, $user, $opening] = $this->openingScenario(published: true);
        $provider = app(RecruitmentProviderService::class)->connect($organization, 'linkedin_jobs', $user);

        $listing = app(RecruitmentJobBoardService::class)->publishOpening($opening, $provider, $user);
        $this->assertSame('published', $listing->status);

        app(JobOpeningService::class)->closeOpening($opening, $user);

        $this->assertSame('closed', $opening->fresh()->status);
        $this->assertSame('closed', RecruitmentJobBoardListing::query()->findOrFail($listing->id)->status);
        $this->assertNotNull(RecruitmentJobBoardListing::query()->findOrFail($listing->id)->closed_at);
    }

    /**
     * @return array{0: Organization, 1: User, 2: JobOpening}
     */
    private function openingScenario(bool $published): array
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

        $opening = JobOpening::factory()->create([
            'organization_id' => $organization->id,
            'job_requisition_id' => $requisition->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'status' => $published ? 'published' : 'draft',
            'publish_date' => $published ? now()->toDateString() : null,
        ]);

        return [$organization, $user, $opening];
    }
}
