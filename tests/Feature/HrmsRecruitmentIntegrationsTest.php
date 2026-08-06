<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\RecruitmentWebhookEndpoint;
use App\Models\User;
use App\Services\Recruitment\RecruitmentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrmsRecruitmentIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_with_permissions_can_view_integrations_index(): void
    {
        [$organization, $hr] = $this->integrationScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.recruitment.integrations.index'))
            ->assertOk();
    }

    public function test_connect_provider(): void
    {
        [$organization, $hr] = $this->integrationScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.recruitment.integrations.connect', ['provider' => 'google_calendar']))
            ->assertRedirect();

        $provider = app(RecruitmentProviderService::class)->findProvider($organization, 'google_calendar');
        $this->assertNotNull($provider);
        $this->assertTrue($provider->isConnected());
    }

    public function test_create_communication_template(): void
    {
        [$organization, $hr] = $this->integrationScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.recruitment.communication-templates.store'), [
                'key' => 'interview_invitation',
                'name' => 'Interview Invite',
                'channel' => 'email',
                'subject' => 'Interview with {{company_name}}',
                'body' => 'Hello {{candidate_name}}, please join for {{job_title}}.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recruitment_communication_templates', [
            'organization_id' => $organization->id,
            'key' => 'interview_invitation',
            'name' => 'Interview Invite',
            'status' => 'draft',
        ]);
    }

    public function test_tenant_isolation_org_a_cannot_see_org_b_webhook_endpoints(): void
    {
        [$organizationA, $hrA] = $this->integrationScenario();
        $organizationB = Organization::factory()->create();
        $hrB = User::factory()->create();
        $organizationB->addMember($hrB, 'hr');

        RecruitmentWebhookEndpoint::query()->create([
            'organization_id' => $organizationB->id,
            'name' => 'Secret Org B Endpoint',
            'url' => 'https://hooks.example.com/org-b-only',
            'events' => ['application_submitted'],
            'is_active' => true,
            'created_by' => $hrB->id,
            'updated_by' => $hrB->id,
        ]);

        $this->actingAs($hrA)->withSession(['current_organization_id' => $organizationA->id])
            ->get(route('hrms.recruitment.webhooks.index'))
            ->assertOk()
            ->assertDontSee('Secret Org B Endpoint')
            ->assertDontSee('https://hooks.example.com/org-b-only');
    }

    public function test_permission_denial_without_recruitment_integration_view(): void
    {
        [$organization] = $this->integrationScenario();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.recruitment.integrations.index'))
            ->assertForbidden();
    }

    public function test_audit_log_created_on_provider_connect(): void
    {
        [$organization, $hr] = $this->integrationScenario();
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)
            ->post(route('hrms.recruitment.integrations.connect', ['provider' => 'linkedin_jobs']))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'recruitment_provider_connected',
        ]);
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function integrationScenario(): array
    {
        $organization = Organization::factory()->create();
        $hr = User::factory()->create();
        $organization->addMember($hr, 'hr');

        return [$organization, $hr];
    }
}
