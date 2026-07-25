<?php

namespace Tests\Unit\Recruitment;

use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\RecruitmentCommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentCommunicationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_template(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $template = app(RecruitmentCommunicationService::class)->createTemplate($organization, [
            'key' => 'interview_invitation',
            'name' => 'Interview Invite',
            'channel' => 'email',
            'subject' => 'Interview for {{job_title}}',
            'body' => 'Hello {{candidate_name}}, join us at {{company_name}}.',
        ], $user);

        $this->assertSame('draft', $template->status);
        $this->assertSame('interview_invitation', $template->key);
        $this->assertContains('candidate_name', $template->variables);
        $this->assertContains('job_title', $template->variables);
        $this->assertDatabaseHas('recruitment_communication_templates', [
            'id' => $template->id,
            'organization_id' => $organization->id,
            'status' => 'draft',
        ]);
    }

    public function test_render_requires_active_approved_template(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(RecruitmentCommunicationService::class);

        $template = $service->createTemplate($organization, [
            'key' => 'application_received',
            'name' => 'Application Received',
            'channel' => 'email',
            'subject' => 'Thanks {{candidate_name}}',
            'body' => 'We received your application for {{job_title}}.',
        ], $user);

        $this->expectException(ValidationException::class);
        $service->render($template, [
            'candidate_name' => 'Jane',
            'job_title' => 'Engineer',
        ]);
    }

    public function test_approve_activates_and_render_replaces_variables(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $service = app(RecruitmentCommunicationService::class);

        $template = $service->createTemplate($organization, [
            'key' => 'offer_sent',
            'name' => 'Offer Sent',
            'channel' => 'email',
            'subject' => 'Offer for {{candidate_name}}',
            'body' => 'Join {{company_name}} as {{job_title}} at {{offer_salary}}.',
        ], $user);

        $service->submitForApproval($template, $user);
        $active = $service->approveTemplate($template->fresh(), $user);

        $this->assertSame('active', $active->status);
        $this->assertNotNull($active->approved_at);
        $this->assertSame($user->id, $active->approved_by);

        $rendered = $service->render($active, [
            'candidate_name' => 'Alex Doe',
            'company_name' => 'NovaCRM',
            'job_title' => 'Backend Engineer',
            'offer_salary' => '120000',
        ]);

        $this->assertSame('Offer for Alex Doe', $rendered['subject']);
        $this->assertSame(
            'Join NovaCRM as Backend Engineer at 120000.',
            $rendered['body']
        );
        $this->assertSame('email', $rendered['channel']);
        $this->assertSame('offer_sent', $rendered['key']);
    }
}
