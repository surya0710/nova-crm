<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\OrganizationOnboarding;
use App\Models\PlatformUser;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Platform\OnboardingWizardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationOnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function platformOwner(): PlatformUser
    {
        return PlatformUser::factory()->create(['role' => 'platform-owner']);
    }

    public function test_start_draft_and_resume(): void
    {
        $actor = $this->platformOwner();
        $wizard = app(OnboardingWizardService::class);

        $onboarding = $wizard->start($actor, ['name' => 'Acme Draft']);
        $this->assertSame(OrganizationOnboarding::STATUS_DRAFT, $onboarding->status);
        $this->assertSame('organization', $onboarding->current_step);

        $wizard->saveDraft($onboarding, 'organization', ['name' => 'Acme Corp', 'plan' => 'enterprise']);
        $onboarding->refresh();
        $this->assertSame('Acme Corp', $onboarding->step_data['organization']['name']);

        $resumed = $wizard->resume($onboarding->fresh());
        $this->assertSame(OrganizationOnboarding::STATUS_IN_PROGRESS, $resumed->status);
    }

    public function test_wizard_provisions_org_modules_structure_users_and_finishes(): void
    {
        $actor = $this->platformOwner();
        $wizard = app(OnboardingWizardService::class);
        $onboarding = $wizard->start($actor);

        $wizard->completeStep($onboarding, 'organization', [
            'name' => 'Nova Onboard Co',
            'plan' => 'enterprise',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'email' => 'ops@nova-onboard.test',
            'industry' => 'Technology',
        ], false, $actor);

        $onboarding->refresh();
        $this->assertNotNull($onboarding->organization_id);
        $this->assertDatabaseHas('organizations', [
            'id' => $onboarding->organization_id,
            'name' => 'Nova Onboard Co',
        ]);

        $wizard->completeStep($onboarding->fresh(), 'modules', [
            'plan' => 'enterprise',
            'modules' => ['crm', 'hrms', 'projects'],
        ], false, $actor);

        $org = Organization::query()->findOrFail($onboarding->fresh()->organization_id);
        $this->assertNotEmpty(app(\App\Services\Dashboard\ModuleSubscriptionService::class)->enabledModules($org));

        $wizard->completeStep($onboarding->fresh(), 'structure', [
            'branch' => ['name' => 'HQ', 'code' => 'HQ'],
            'department' => ['name' => 'Ops', 'code' => 'OPS'],
            'designation' => ['name' => 'Associate', 'code' => 'ASC'],
            'shift' => ['name' => 'Day', 'code' => 'DAY', 'start_time' => '09:00:00', 'end_time' => '18:00:00'],
        ], false, $actor);

        $this->assertTrue(
            Branch::query()->withoutGlobalScopes()->where('organization_id', $org->id)->where('code', 'HQ')->exists()
        );

        $wizard->completeStep($onboarding->fresh(), 'users', [
            'administrator' => [
                'name' => 'Org Admin',
                'email' => 'admin@nova-onboard.test',
                'role' => 'organization-owner',
                'send_invitation' => true,
                'notify' => false,
            ],
        ], false, $actor);

        $this->assertTrue(User::query()->where('email', 'admin@nova-onboard.test')->exists());
        $this->assertTrue(
            UserInvitation::query()->withoutGlobalScopes()->where('organization_id', $org->id)->exists()
        );

        $wizard->completeStep($onboarding->fresh(), 'imports', [
            'entities' => ['lead', 'employee'],
            'deferred' => true,
        ], false, $actor);

        $wizard->completeStep($onboarding->fresh(), 'branding', [
            'primary_color' => '#1d4ed8',
            'email_from_name' => 'Nova Onboard',
            'document_footer' => 'Confidential',
        ], false, $actor);

        $wizard->completeStep($onboarding->fresh(), 'communication', [
            'mail_enabled' => '1',
            'mail_driver' => 'log',
            'mail_from_address' => 'noreply@nova-onboard.test',
            'mail_from_name' => 'Nova Onboard',
            'verify' => '1',
        ], false, $actor);

        $wizard->completeStep($onboarding->fresh(), 'providers', [
            'acknowledged' => '1',
            'skipped_providers' => ['whatsapp', 'sms'],
        ], false, $actor);

        $onboarding = $onboarding->fresh();
        $this->assertSame('go_live', $onboarding->current_step);

        $checklist = $wizard->buildChecklist($onboarding);
        $this->assertTrue(collect($checklist)->firstWhere('key', 'organization_created')['passed']);
        $this->assertTrue(collect($checklist)->firstWhere('key', 'rbac_provisioned')['passed']);
        $this->assertTrue(collect($checklist)->firstWhere('key', 'invitations_sent')['passed']);

        $finished = $wizard->finish($onboarding, $actor);
        $this->assertSame(OrganizationOnboarding::STATUS_COMPLETED, $finished->status);
        $this->assertSame(100, $finished->progress_percent);
    }

    public function test_skip_optional_steps_and_web_index(): void
    {
        $actor = $this->platformOwner();
        $wizard = app(OnboardingWizardService::class);
        $onboarding = $wizard->start($actor);

        $wizard->completeStep($onboarding, 'organization', [
            'name' => 'Skip Corp',
            'plan' => 'starter',
        ], false, $actor);

        $wizard->completeStep($onboarding->fresh(), 'modules', [
            'modules' => ['crm'],
        ], false, $actor);

        $wizard->completeStep($onboarding->fresh(), 'structure', [], true, $actor);
        $this->assertTrue($onboarding->fresh()->isStepSkipped('structure'));

        $this->actingAs($actor, 'platform')
            ->get(route('platform.onboarding.index'))
            ->assertOk();

        $this->actingAs($actor, 'platform')
            ->get(route('platform.onboarding.show', $onboarding->fresh()))
            ->assertOk();
    }

    public function test_api_create_progress_and_step(): void
    {
        $actor = $this->platformOwner();

        $response = $this->actingAs($actor, 'platform')
            ->postJson(route('platform.api.onboarding.store'));

        $response->assertCreated();
        $id = $response->json('onboarding.id');
        $this->assertNotNull($id);

        $this->actingAs($actor, 'platform')
            ->getJson(route('platform.api.onboarding.progress', $id))
            ->assertOk()
            ->assertJsonPath('current_step', 'organization');

        $this->actingAs($actor, 'platform')
            ->postJson(route('platform.api.onboarding.steps', $id), [
                'step' => 'organization',
                'payload' => [
                    'name' => 'API Org',
                    'plan' => 'starter',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('progress.current_step', 'modules');
    }

    public function test_dashboard_includes_onboarding_summary(): void
    {
        $actor = $this->platformOwner();
        app(OnboardingWizardService::class)->start($actor);

        $this->actingAs($actor, 'platform')
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Organization Onboarding');
    }
}
