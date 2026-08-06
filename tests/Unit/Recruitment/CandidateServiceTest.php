<?php

namespace Tests\Unit\Recruitment;

use App\Models\Candidate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Recruitment\CandidateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CandidateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_email_must_be_unique_within_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        app(CandidateService::class)->createCandidate([
            'organization_id' => $organization->id,
            'first_name' => 'Alex',
            'last_name' => 'One',
            'email' => 'alex@example.com',
        ], $user);

        $this->expectException(ValidationException::class);
        app(CandidateService::class)->createCandidate([
            'organization_id' => $organization->id,
            'first_name' => 'Alex',
            'last_name' => 'Two',
            'email' => 'alex@example.com',
        ], $user);
    }

    public function test_same_email_allowed_in_different_organizations(): void
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        app(CandidateService::class)->createCandidate([
            'organization_id' => $orgA->id,
            'first_name' => 'Sam',
            'last_name' => 'Shared',
            'email' => 'shared@example.com',
        ], $user);

        $candidate = app(CandidateService::class)->createCandidate([
            'organization_id' => $orgB->id,
            'first_name' => 'Sam',
            'last_name' => 'Shared',
            'email' => 'shared@example.com',
        ], $user);

        $this->assertInstanceOf(Candidate::class, $candidate);
    }
}
