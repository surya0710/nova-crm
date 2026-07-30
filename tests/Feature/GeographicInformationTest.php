<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\Export\ExportPlatformService;
use App\Services\Import\Adapters\CustomerImportAdapter;
use App\Services\Import\Adapters\LeadImportAdapter;
use App\Services\Import\ImportPlatformService;
use App\Services\ReportService;
use App\Services\Search\CrmCustomerSearchProvider;
use App\Services\Search\CrmLeadSearchProvider;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeographicInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_create_update_and_detail_support_trimmed_geography(): void
    {
        [$user, $organization] = $this->setupOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.store'), [
                'name' => 'Geographic Lead',
                'source' => 'manual_entry',
                'priority' => 'medium',
                'status' => 'new',
                'address_line_1' => '  12 Main Road  ',
                'city' => '  New Delhi ',
                'state' => ' Delhi ',
                'country' => ' India ',
                'postal_code' => ' 110001 ',
            ])
            ->assertRedirect();

        $lead = Lead::query()->where('name', 'Geographic Lead')->firstOrFail();
        $this->assertSame('12 Main Road', $lead->address_line_1);
        $this->assertSame('New Delhi', $lead->city);
        $this->assertSame('Delhi', $lead->state);
        $this->assertSame('India', $lead->country);
        $this->assertSame('110001', $lead->postal_code);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('leads.update', $lead), [
                'name' => $lead->name,
                'source' => $lead->source,
                'priority' => $lead->priority,
                'status' => $lead->status,
                'state' => ' Maharashtra ',
                'country' => ' India ',
            ])
            ->assertRedirect(route('leads.show', $lead));

        $lead->refresh();
        $this->assertSame('Maharashtra', $lead->state);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Maharashtra')
            ->assertSee('India');
    }

    public function test_customer_create_update_and_detail_support_geography(): void
    {
        [$user, $organization] = $this->setupOwner();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.store'), [
                'name' => 'Geographic Customer',
                'status' => 'active',
                'address_line_1' => '  8 Market Street ',
                'city' => ' Mumbai ',
                'state' => ' Maharashtra ',
                'country' => ' India ',
                'postal_code' => ' 400001 ',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('name', 'Geographic Customer')->firstOrFail();
        $this->assertSame('Maharashtra', $customer->state);
        $this->assertSame('India', $customer->country);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('customers.update', $customer), [
                'name' => $customer->name,
                'status' => $customer->status,
                'state' => ' Karnataka ',
                'country' => ' India ',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Karnataka', $customer->state);
    }

    public function test_lead_conversion_copies_complete_address_to_customer(): void
    {
        [$user, $organization] = $this->setupOwner();
        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Conversion Geography',
            'email' => 'geo-conversion@example.test',
            'status' => 'qualified',
            'address_line_1' => '15 Central Avenue',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'country' => 'India',
            'postal_code' => '110001',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.convert', $lead), [
                'name' => $lead->name,
                'email' => $lead->email,
                'create_opportunity' => '0',
            ])
            ->assertRedirect();

        $customer = Customer::query()->where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame($lead->address_line_1, $customer->address_line_1);
        $this->assertSame($lead->city, $customer->city);
        $this->assertSame($lead->state, $customer->state);
        $this->assertSame($lead->country, $customer->country);
        $this->assertSame($lead->postal_code, $customer->postal_code);
    }

    public function test_search_and_filters_support_geography_without_cross_tenant_results(): void
    {
        [$user, $organization] = $this->setupOwner();
        [, $otherOrganization] = $this->setupOwner();

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Delhi Lead',
            'state' => 'Delhi',
            'country' => 'India',
        ]);
        Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Other Lead',
            'state' => 'Texas',
            'country' => 'USA',
        ]);
        Lead::factory()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Foreign Delhi Lead',
            'state' => 'Delhi',
            'country' => 'India',
        ]);
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'India Customer',
            'state' => 'Maharashtra',
            'country' => 'India',
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['search' => 'Delhi']))
            ->assertOk()
            ->assertSee('Delhi Lead')
            ->assertDontSee('Other Lead')
            ->assertDontSee('Foreign Delhi Lead');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index', ['state' => ['Delhi'], 'country' => ['India']]))
            ->assertOk()
            ->assertSee('Delhi Lead')
            ->assertDontSee('Other Lead');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index', ['search' => 'India', 'country' => 'India']))
            ->assertOk()
            ->assertSee('India Customer');
    }

    public function test_lead_and_customer_apis_create_update_list_and_show_geography(): void
    {
        [$user, $organization] = $this->setupOwner();
        Sanctum::actingAs($user, ['*']);
        $headers = $this->apiHeaders($organization);

        $leadResponse = $this->postJson('/api/v1/leads', [
            'name' => 'API Geographic Lead',
            'phone' => '9876543210',
            'source' => 'api',
            'state' => ' Delhi ',
            'country' => ' India ',
        ], $headers)->assertCreated();

        $leadId = $leadResponse->json('lead_id');
        $this->getJson("/api/v1/leads/{$leadId}", $headers)
            ->assertOk()
            ->assertJsonPath('data.state', 'Delhi')
            ->assertJsonPath('data.country', 'India');

        $this->patchJson("/api/v1/leads/{$leadId}", [
            'state' => 'Maharashtra',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.state', 'Maharashtra');

        $customerResponse = $this->postJson('/api/v1/customers', [
            'name' => 'API Geographic Customer',
            'state' => ' Karnataka ',
            'country' => ' India ',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.state', 'Karnataka')
            ->assertJsonPath('data.country', 'India');

        $customerId = $customerResponse->json('data.id');
        $this->patchJson("/api/v1/customers/{$customerId}", [
            'state' => 'Tamil Nadu',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.state', 'Tamil Nadu');

        $this->getJson('/api/v1/customers?search=India&country=India', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $customerId);
    }

    public function test_lead_and_customer_imports_support_optional_geography(): void
    {
        Storage::fake('local');
        [$user, $organization] = $this->setupOwner();
        $imports = app(ImportPlatformService::class);

        $leadFile = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "Full Name,Email,Phone,City,State,Country\n".
            "Imported Geo Lead,geo-lead@example.test,+15550100140,New Delhi,Delhi,India\n".
            "Legacy Lead,legacy-lead@example.test,+15550100141,,,\n",
        );
        $leadSession = $imports->upload($organization, 'lead', $leadFile, $user);
        $imports->executeImport($imports->validate($leadSession, $user), $user);

        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'email' => 'geo-lead@example.test',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'country' => 'India',
        ]);
        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'email' => 'legacy-lead@example.test',
            'state' => null,
            'country' => null,
        ]);

        $customerFile = UploadedFile::fake()->createWithContent(
            'customers.csv',
            "Full Name,Email,State,Country\n".
            "Imported Geo Customer,geo-customer@example.test,Maharashtra,India\n",
        );
        $customerSession = $imports->upload($organization, 'customer', $customerFile, $user);
        $imports->executeImport($imports->validate($customerSession, $user), $user);

        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'email' => 'geo-customer@example.test',
            'state' => 'Maharashtra',
            'country' => 'India',
        ]);
    }

    public function test_exports_and_reports_include_geographic_dimensions(): void
    {
        Storage::fake('local');
        config(['export.disk' => 'local', 'export.queue_threshold_rows' => 1000]);
        [$user, $organization] = $this->setupOwner();

        $lead = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Reported Lead',
            'state' => 'Delhi',
            'country' => 'India',
            'status' => 'converted',
            'converted_at' => now(),
        ]);
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Reported Customer',
            'state' => 'Delhi',
            'country' => 'India',
        ]);
        Payment::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'amount' => 2500,
            'recorded_by' => $user->id,
        ]);

        foreach ([
            ['lead', $lead->id, 'name'],
            ['customer', $customer->id, 'display_name'],
        ] as [$entity, $id, $nameColumn]) {
            $session = app(ExportPlatformService::class)->start(
                $organization,
                $user,
                $entity,
                'csv',
                ['mode' => 'ids', 'ids' => [$id]],
                [$nameColumn, 'state', 'country'],
            );

            $contents = Storage::disk('local')->get($session->file_path);
            $this->assertStringContainsString('State', $contents);
            $this->assertStringContainsString('Country', $contents);
            $this->assertStringContainsString('India', $contents);
        }

        $report = app(ReportService::class)->compile($organization, null, 'state');
        $this->assertSame('state', $report['geographic_group']);
        $this->assertSame('Delhi', $report['leads_by_geography']->first()->state);
        $this->assertSame('Delhi', $report['customers_by_geography']->first()->state);
        $this->assertSame('Delhi', $report['revenue_by_geography']->first()->geography);
        $this->assertSame('Delhi', $report['lead_conversion_by_geography']->first()['geography']);
    }

    public function test_global_crm_search_includes_geography(): void
    {
        [$user, $organization] = $this->setupOwner();

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Global Geography Lead',
            'state' => 'Delhi',
        ]);
        Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Global Geography Customer',
            'company' => null,
            'country' => 'India',
        ]);

        $leadResults = app(CrmLeadSearchProvider::class)->search($user, $organization, 'Delhi');
        $customerResults = app(CrmCustomerSearchProvider::class)->search($user, $organization, 'India');

        $this->assertSame('Global Geography Lead', $leadResults->first()['title']);
        $this->assertSame('Global Geography Customer', $customerResults->first()['title']);
    }

    public function test_standard_geographic_import_fields_win_metadata_key_collisions(): void
    {
        [, $organization] = $this->setupOwner();

        foreach (['lead', 'customer'] as $entityType) {
            MetadataFieldDefinition::query()->create([
                'organization_id' => $organization->id,
                'entity_type' => $entityType,
                'key' => 'state',
                'label' => 'Legacy State Metadata',
                'type' => 'text',
                'status' => 'active',
                'published_at' => now(),
                'activated_at' => now(),
            ]);
        }

        foreach ([app(LeadImportAdapter::class), app(CustomerImportAdapter::class)] as $adapter) {
            $stateFields = collect($adapter->fieldDefinitions())->where('key', 'state');

            $this->assertCount(1, $stateFields);
            $this->assertFalse($stateFields->first()->supportsMetadata);
        }
    }

    /**
     * @return array{User, Organization}
     */
    protected function setupOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');
        app(TenantContext::class)->set($organization);

        return [$user, $organization];
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
