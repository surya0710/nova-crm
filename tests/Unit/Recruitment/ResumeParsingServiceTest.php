<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\ResumeParsingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResumeParsingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parse_completes_with_internal_provider(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        $request = app(ResumeParsingService::class)->requestParse(
            $organization,
            [
                'filename' => 'jane-resume.pdf',
                'mime_type' => 'application/pdf',
            ],
            null,
            null,
            $user,
            'internal_resume_parser',
        );

        $this->assertSame('completed', $request->status);
        $this->assertTrue($request->parsed_data['ok'] ?? false);
        $this->assertSame('jane-resume.pdf', $request->parsed_data['raw']['filename'] ?? null);
        $this->assertDatabaseHas('recruitment_resume_parse_requests', [
            'id' => $request->id,
            'organization_id' => $organization->id,
            'status' => 'completed',
        ]);
    }

    public function test_apply_without_overwrite_when_candidate_has_email_throws(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');
        $candidate = Candidate::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'existing@example.com',
        ]);

        $service = app(ResumeParsingService::class);
        $request = $service->requestParse(
            $organization,
            ['filename' => 'resume.pdf', 'mime_type' => 'application/pdf'],
            $candidate,
            null,
            $user,
        );

        $request->update([
            'parsed_data' => array_merge($request->parsed_data ?? [], [
                'ok' => true,
                'contact' => [
                    'email' => 'parsed@example.com',
                    'phone' => '555-0100',
                ],
            ]),
            'status' => 'completed',
        ]);

        $this->expectException(ValidationException::class);
        $service->applyParsedData($request->fresh(), $candidate, $user, false);
    }

    public function test_apply_with_overwrite_confirmed_works(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');
        $candidate = Candidate::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'existing@example.com',
            'phone' => '111-1111',
        ]);

        $service = app(ResumeParsingService::class);
        $request = $service->requestParse(
            $organization,
            ['filename' => 'resume.pdf', 'mime_type' => 'application/pdf'],
            $candidate,
            null,
            $user,
        );

        $request->update([
            'parsed_data' => array_merge($request->parsed_data ?? [], [
                'ok' => true,
                'contact' => [
                    'email' => 'parsed@example.com',
                    'phone' => '555-0100',
                ],
            ]),
            'status' => 'completed',
        ]);

        $updated = $service->applyParsedData($request->fresh(), $candidate, $user, true);

        $this->assertSame('parsed@example.com', $updated->email);
        $this->assertSame('555-0100', $updated->phone);
        $this->assertTrue($request->fresh()->applied_to_candidate);
        $this->assertTrue($request->fresh()->overwrite_confirmed);
    }
}
