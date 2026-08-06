<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeExperience;
use App\Models\EmployeeSkill;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hrms\EmployeeProfileService;
use App\Services\Hrms\EmployeeTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileCareerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }

    public function test_skills_certifications_education_experience_and_emergency_contacts_crud(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'date_of_birth' => '1990-01-15',
            'email' => 'jane@example.com',
            'mobile' => '9999999999',
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.skills.store', $employee), [
            'skill' => 'Laravel',
            'proficiency' => 'advanced',
            'years_of_experience' => 5,
            'last_used' => now()->toDateString(),
            'notes' => 'Primary stack',
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_skills', [
            'employee_id' => $employee->id,
            'skill' => 'Laravel',
            'proficiency' => 'advanced',
        ]);

        $skill = EmployeeSkill::query()->firstOrFail();
        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.skills.update', [$employee, $skill]), [
            'skill' => 'Laravel',
            'proficiency' => 'expert',
            'years_of_experience' => 6,
        ])->assertRedirect();
        $this->assertSame('expert', $skill->fresh()->proficiency);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.certifications.store', $employee), [
            'name' => 'AWS Solutions Architect',
            'issuing_organization' => 'Amazon',
            'credential_number' => 'AWS-123',
            'issue_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addMonths(2)->toDateString(),
            'credential_url' => 'https://example.com/cert',
            'status' => 'active',
        ])->assertRedirect();

        $cert = EmployeeCertification::query()->firstOrFail();
        $this->assertSame('expiring_soon', $cert->display_status);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.educations.store', $employee), [
            'institution' => 'MIT',
            'degree' => 'BSc',
            'field_of_study' => 'Computer Science',
            'start_date' => '2010-09-01',
            'end_date' => '2014-06-01',
            'grade' => 'First Class',
            'description' => 'CS degree',
        ])->assertRedirect();

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.experiences.store', $employee), [
            'company' => 'Acme',
            'title' => 'Developer',
            'employment_type' => 'full_time',
            'start_date' => '2015-01-01',
            'end_date' => '2018-12-31',
            'technologies' => 'PHP, MySQL',
            'description' => 'Built CRM modules',
        ])->assertRedirect();

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.emergency-contacts.store', $employee), [
            'name' => 'Alex Doe',
            'relationship' => 'Spouse',
            'phone' => '1111111111',
            'alternate_mobile' => '2222222222',
            'email' => 'alex@example.com',
            'address' => '123 Main St',
            'is_primary' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_educations', ['employee_id' => $employee->id, 'institution' => 'MIT']);
        $this->assertDatabaseHas('employee_experiences', ['employee_id' => $employee->id, 'company' => 'Acme']);
        $this->assertDatabaseHas('employee_emergency_contacts', [
            'employee_id' => $employee->id,
            'name' => 'Alex Doe',
            'is_primary' => true,
        ]);

        $this->actingAs($hr)->withSession($session)
            ->delete(route('hrms.employees.skills.destroy', [$employee, $skill]))
            ->assertRedirect();
        $this->assertDatabaseMissing('employee_skills', ['id' => $skill->id]);
    }

    public function test_profile_completion_and_reporting_structure(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $manager = Employee::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'Manager',
        ]);
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $manager->id,
            'date_of_birth' => '1992-05-01',
            'email' => 'emp@example.com',
            'mobile' => '8888888888',
        ]);
        Employee::factory()->create([
            'organization_id' => $organization->id,
            'reporting_manager_id' => $employee->id,
            'first_name' => 'Reportee',
        ]);

        EmployeeEmergencyContact::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'name' => 'Contact',
            'phone' => '123',
            'is_primary' => true,
        ]);
        EmployeeSkill::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'skill' => 'PHP',
            'proficiency' => 'advanced',
        ]);
        EmployeeEducation::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'institution' => 'Uni',
            'degree' => 'BA',
        ]);
        EmployeeExperience::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'company' => 'Prev Co',
            'title' => 'Analyst',
        ]);

        $profile = app(EmployeeProfileService::class);
        $completion = $profile->profileCompletion($employee->fresh());
        $this->assertGreaterThanOrEqual(70, $completion['percentage']);

        $structure = $profile->reportingStructure($employee->fresh());
        $this->assertSame($manager->id, $structure['reporting_manager']?->id);
        $this->assertTrue($structure['direct_reportees']->contains(fn ($e) => $e->first_name === 'Reportee'));
    }

    public function test_employee_show_renders_profile_enrichment_and_rbac(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        EmployeeSkill::query()->create([
            'organization_id' => $organization->id,
            'employee_id' => $employee->id,
            'skill' => 'React',
            'proficiency' => 'intermediate',
        ]);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.employees.show', $employee))
            ->assertOk()
            ->assertSee('React')
            ->assertSee('Profile completion');

        $outsider = User::factory()->create();
        $organization->addMember($outsider, 'employee');
        $this->actingAs($outsider)->withSession($session)
            ->get(route('hrms.employees.show', $employee))
            ->assertForbidden();
    }

    public function test_timeline_aggregates_without_duplicate_store_and_org_isolation(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'joining_date' => now()->subYear()->toDateString(),
        ]);

        $otherOrg = Organization::factory()->create();
        $foreign = Employee::factory()->create(['organization_id' => $otherOrg->id]);

        $timeline = app(EmployeeTimelineService::class)->timelineForEmployee($employee);
        $this->assertTrue($timeline->contains(fn ($e) => $e['type'] === 'joined'));

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.employees.show', $foreign))
            ->assertForbidden();
    }

    public function test_employee_update_syncs_career_sections(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $session = ['current_organization_id' => $organization->id];
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'employment_type' => 'full_time',
            'status' => 'active',
            'skills' => [
                ['skill' => 'Docker', 'proficiency' => 'beginner'],
            ],
            'certifications' => [
                ['name' => 'CKA', 'status' => 'active'],
            ],
            'emergency_contacts' => [
                ['name' => 'Sam', 'phone' => '555', 'is_primary' => '1'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_skills', ['employee_id' => $employee->id, 'skill' => 'Docker']);
        $this->assertDatabaseHas('employee_certifications', ['employee_id' => $employee->id, 'name' => 'CKA']);
        $this->assertDatabaseHas('employee_emergency_contacts', ['employee_id' => $employee->id, 'name' => 'Sam']);
    }
}
