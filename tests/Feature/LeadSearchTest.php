<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_search_matches_supported_text_fields_and_customer_name_partially(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();

        $namedLead = $this->lead($organization, $user, [
            'name' => 'Rahul Sharma',
            'company' => 'Unrelated Company',
            'email' => 'unrelated@example.test',
        ]);
        $companyLead = $this->lead($organization, $user, [
            'name' => 'Company Prospect',
            'company' => 'Acme Industries',
            'email' => 'company@example.test',
        ]);
        $emailLead = $this->lead($organization, $user, [
            'name' => 'Email Prospect',
            'company' => 'Email Company',
            'email' => 'contact@brightmail.test',
        ]);
        $customerLead = $this->lead($organization, $user, [
            'name' => 'Converted Prospect',
            'company' => 'Converted Company',
            'email' => 'converted@example.test',
        ]);
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'lead_id' => $customerLead->id,
            'name' => 'John Smith',
            'company' => 'Customer Company',
            'created_by' => $user->id,
        ]);

        foreach ([
            'rAhUl' => $namedLead->name,
            'har' => $namedLead->name,
            'Acme' => $companyLead->name,
            'brightmail' => $emailLead->name,
            'John' => $customerLead->name,
            'Smi' => $customerLead->name,
        ] as $search => $expectedName) {
            $this->actingAs($user)
                ->withSession(['current_organization_id' => $organization->id])
                ->get(route('leads.index', ['search' => $search]))
                ->assertOk()
                ->assertSee($expectedName);
        }
    }

    public function test_web_search_normalizes_exact_partial_and_formatted_mobile_numbers(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $lead = $this->lead($organization, $user, [
            'name' => 'Mobile Match',
            'phone' => '98765 43210',
        ]);
        $this->lead($organization, $user, [
            'name' => 'Other Mobile',
            'phone' => '11111 22222',
        ]);

        foreach (['9876543210', '9876', '6543', '3210', '+91 9876543210', '(+91)9876543210'] as $search) {
            $this->actingAs($user)
                ->withSession(['current_organization_id' => $organization->id])
                ->get(route('leads.index', ['search' => $search]))
                ->assertOk()
                ->assertSee($lead->name)
                ->assertDontSee('Other Mobile');
        }
    }

    public function test_web_search_composes_with_filters_pagination_and_tenant_scope(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        [, $otherOrganization] = $this->setupUserWithOrg();

        $match = $this->lead($organization, $user, [
            'name' => 'Rahul Qualified',
            'status' => 'qualified',
        ]);
        $this->lead($organization, $user, [
            'name' => 'Rahul New',
            'status' => 'new',
        ]);
        $this->lead($otherOrganization, User::factory()->create(), [
            'name' => 'Rahul Other Tenant',
            'status' => 'qualified',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', [
                'search' => 'Rahul',
                'status' => 'qualified',
            ]));

        $response->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Rahul New')
            ->assertDontSee('Rahul Other Tenant');

        Lead::factory()->count(16)->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Paged Search Result',
        ]);

        $paginatedResponse = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['search' => 'Paged Search']));

        $paginatedResponse->assertOk();
        $this->assertStringContainsString(
            'search=Paged%20Search',
            $paginatedResponse->viewData('leads')->nextPageUrl(),
        );
    }

    public function test_api_uses_the_same_search_with_filters_pagination_and_tenant_scope(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        [, $otherOrganization] = $this->setupUserWithOrg();

        $match = $this->lead($organization, $user, [
            'name' => 'API Mobile Match',
            'phone' => '+91 98765 43210',
            'status' => 'qualified',
        ]);
        $this->lead($organization, $user, [
            'name' => 'API Wrong Status',
            'phone' => '9876543210',
            'status' => 'new',
        ]);
        $this->lead($otherOrganization, User::factory()->create(), [
            'name' => 'API Other Tenant',
            'phone' => '9876543210',
            'status' => 'qualified',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/leads?'.http_build_query([
            'search' => '6543',
            'status' => 'qualified',
            'per_page' => 1,
        ]), $this->apiHeaders($organization));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonPath('meta.per_page', 1);

        $this->assertStringContainsString('search=6543', $response->json('links.first'));
    }

    /**
     * @return array{User, Organization}
     */
    protected function setupUserWithOrg(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    protected function lead(Organization $organization, User $creator, array $attributes): Lead
    {
        return Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function apiHeaders(Organization $organization): array
    {
        return [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ];
    }
}
