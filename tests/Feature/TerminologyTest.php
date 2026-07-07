<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationTerminology;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_are_used_without_custom_settings(): void
    {
        $organization = Organization::factory()->create();

        app(TenantContext::class)->set($organization);

        $this->assertSame('Leads', crm_term('leads'));
        $this->assertSame('Customers', crm_term('customers'));
    }

    public function test_industry_preset_is_applied(): void
    {
        $organization = Organization::factory()->create([
            'settings' => ['industry_type' => 'real_estate'],
        ]);

        app(TenantContext::class)->set($organization);

        $this->assertSame('Prospects', crm_term('leads'));
        $this->assertSame('Clients', crm_term('customers'));
        $this->assertSame('Proposals', crm_term('quotations'));
    }

    public function test_custom_overrides_take_precedence(): void
    {
        $organization = Organization::factory()->create([
            'settings' => [
                'industry_type' => 'real_estate',
                'terminology' => [
                    'leads' => 'Hot Prospects',
                ],
            ],
        ]);

        app(TenantContext::class)->set($organization);

        $this->assertSame('Hot Prospects', crm_term('leads'));
        $this->assertSame('Clients', crm_term('customers'));
    }

    public function test_organization_owner_can_update_terminology_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch('/organization/settings', [
                'name' => $organization->name,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
                'industry_type' => 'healthcare',
                'terminology' => [
                    'leads' => 'Referrals',
                    'customers' => 'Patients',
                ],
            ]);

        $response->assertRedirect(route('organization.edit'));

        $organization->refresh();

        $this->assertSame('healthcare', $organization->settings['industry_type']);
        $this->assertSame('Referrals', $organization->settings['terminology']['leads']);
    }

    public function test_sidebar_reflects_custom_terminology(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'settings' => [
                'industry_type' => 'consulting',
                'terminology' => [],
            ],
        ]);
        $organization->addMember($user, 'manager');

        app(TenantContext::class)->set($organization);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Inquiries', false);
        $response->assertSee('Engagements', false);
    }

    public function test_preset_for_industry_merges_defaults(): void
    {
        $service = app(OrganizationTerminology::class);
        $terms = $service->presetForIndustry('retail');

        $this->assertSame('Shoppers', $terms['leads']);
        $this->assertSame('Receipts', $terms['invoices']);
    }

    public function test_immigration_industry_preset_uses_visa_consultancy_terms(): void
    {
        $organization = Organization::factory()->create([
            'settings' => ['industry_type' => 'immigration'],
        ]);

        app(TenantContext::class)->set($organization);

        $this->assertSame('Enquiries', crm_term('leads'));
        $this->assertSame('Applicants', crm_term('customers'));
        $this->assertSame('Visa Pipeline', crm_term('pipeline'));
        $this->assertSame('Visa Services', crm_term('products'));
        $this->assertSame('Quotes', crm_term('quotations'));
        $this->assertSame('Application', crm_term('deal'));
    }
}
