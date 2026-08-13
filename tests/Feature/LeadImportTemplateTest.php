<?php

namespace Tests\Feature;

use App\Models\MetadataFieldDefinition;
use App\Models\MetadataFieldOption;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\Adapters\LeadImportTemplateAdapter;
use App\Services\Import\ImportTemplateService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LeadImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected ImportTemplateService $templates;

    protected LeadImportTemplateAdapter $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templates = app(ImportTemplateService::class);
        $this->provider = app(LeadImportTemplateAdapter::class);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function setupUserWithOrg(string $role = 'organization-owner'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_csv_template_downloads_with_headers_sample_and_metadata(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $field = MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'visa_interest',
            'label' => 'Visa Interest',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.template.csv'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('lead_import_template.csv', (string) $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $lines = array_values(array_filter(preg_split("/\r\n|\n|\r/", ltrim($csv, "\xEF\xBB\xBF")) ?: []));
        $this->assertGreaterThanOrEqual(2, count($lines));

        $headers = str_getcsv($lines[0]);
        $sample = str_getcsv($lines[1]);

        foreach (['First Name', 'Last Name', 'Email', 'Phone', 'Company', 'Status', 'Source', 'Owner', 'Notes'] as $expected) {
            $this->assertContains($expected, $headers);
        }
        $this->assertContains('Visa Interest', $headers);
        $this->assertSame(count($headers), count($sample));
        $this->assertSame('John', $sample[array_search('First Name', $headers, true)]);
        $this->assertSame('Doe', $sample[array_search('Last Name', $headers, true)]);
        $this->assertSame('john@example.com', $sample[array_search('Email', $headers, true)]);
        $this->assertNotSame('', $sample[array_search('Visa Interest', $headers, true)]);
        $this->assertSame($field->label, 'Visa Interest');
    }

    public function test_excel_template_has_data_instructions_and_lookup_sheets(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $user->update(['name' => 'John Smith', 'email' => 'owner@example.com']);
        app(TenantContext::class)->set($organization);

        $field = MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'preferred_country',
            'label' => 'Preferred Country',
            'type' => 'select',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        MetadataFieldOption::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_definition_id' => $field->id,
            'value' => 'canada',
            'label' => 'Canada',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MetadataFieldOption::query()->create([
            'organization_id' => $organization->id,
            'metadata_field_definition_id' => $field->id,
            'value' => 'inactive_opt',
            'label' => 'Inactive',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.template.xlsx'));

        $response->assertOk();
        $binary = $response->streamedContent();
        $this->assertNotSame('', $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        $path = $tmp.'.xlsx';
        @unlink($tmp);
        file_put_contents($path, $binary);

        try {
            $spreadsheet = IOFactory::load($path);
            $names = $spreadsheet->getSheetNames();

            $this->assertContains('Lead Import', $names);
            $this->assertContains(ImportTemplateService::INSTRUCTIONS_SHEET, $names);
            $this->assertContains(ImportTemplateService::LOOKUP_SHEET, $names);

            $data = $spreadsheet->getSheetByName('Lead Import');
            $this->assertSame('First Name', $data->getCell([1, 1])->getValue());
            $this->assertSame('John', $data->getCell([1, 2])->getValue());
            $this->assertSame('Postal Code', $data->getCell([10, 1])->getValue());
            $this->assertSame('110001', (string) $data->getCell([10, 2])->getValue());

            $instructions = $spreadsheet->getSheetByName(ImportTemplateService::INSTRUCTIONS_SHEET);
            $instructionText = '';
            for ($row = 1; $row <= 20; $row++) {
                $instructionText .= (string) $instructions->getCell([1, $row])->getValue().' ';
            }
            $this->assertStringContainsString('Assignment', $instructionText);
            $this->assertStringContainsString('Duplicate', $instructionText);
            $this->assertStringContainsString('Owner', $instructionText);

            $lookups = $spreadsheet->getSheetByName(ImportTemplateService::LOOKUP_SHEET);
            $lookupText = '';
            for ($col = 1; $col <= 12; $col++) {
                for ($row = 1; $row <= 30; $row++) {
                    $lookupText .= (string) $lookups->getCell([$col, $row])->getValue().' ';
                }
            }

            $this->assertStringContainsString('Status', $lookupText);
            $this->assertStringContainsString('New', $lookupText);
            $this->assertStringContainsString('Source', $lookupText);
            $this->assertStringContainsString('Website', $lookupText);
            $this->assertStringContainsString('Owner', $lookupText);
            $this->assertStringContainsString('John Smith', $lookupText);
            $this->assertStringContainsString('Preferred Country', $lookupText);
            $this->assertStringContainsString('Canada [canada]', $lookupText);
            $this->assertStringNotContainsString('inactive_opt', $lookupText);

            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_metadata_text_and_date_fields_appear_in_template(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'campaign_notes',
            'label' => 'Campaign Notes',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'target_start',
            'label' => 'Target Start',
            'type' => 'date',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        $headers = $this->templates->headers($this->provider, $organization);
        $sample = $this->templates->sampleRow($this->provider, $organization);
        $groups = $this->provider->lookupGroups($organization);

        $this->assertContains('Campaign Notes', $headers);
        $this->assertContains('Target Start', $headers);

        $notesIndex = array_search('Campaign Notes', $headers, true);
        $dateIndex = array_search('Target Start', $headers, true);
        $this->assertSame('Sample value', $sample[$notesIndex]);
        $this->assertSame('2026-07-17', $sample[$dateIndex]);

        $dateGroup = collect($groups)->firstWhere('heading', 'Target Start');
        $this->assertNotNull($dateGroup);
        $this->assertStringContainsString('YYYY-MM-DD', (string) $dateGroup->note);
        $this->assertContains('2026-07-17', $dateGroup->values);

        $textGroup = collect($groups)->firstWhere('heading', 'Campaign Notes');
        $this->assertNotNull($textGroup);
        $this->assertSame([], $textGroup->values);
        $this->assertStringContainsString('Free-text', (string) $textGroup->note);
    }

    public function test_template_enforces_tenant_isolation_for_metadata_and_owners(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        $userA->update(['name' => 'Org A Owner', 'email' => 'orga@example.com']);
        [$userB, $orgB] = $this->setupUserWithOrg();
        $userB->update(['name' => 'Org B Only', 'email' => 'orgb@example.com']);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $orgA->id,
            'entity_type' => 'lead',
            'key' => 'org_a_only',
            'label' => 'Org A Field',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $orgB->id,
            'entity_type' => 'lead',
            'key' => 'org_b_only',
            'label' => 'Org B Field',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        app(TenantContext::class)->set($orgA);
        $headersA = $this->templates->headers($this->provider, $orgA);
        $ownersA = collect($this->provider->lookupGroups($orgA))->firstWhere('heading', 'Owner')?->values ?? [];

        $this->assertContains('Org A Field', $headersA);
        $this->assertNotContains('Org B Field', $headersA);
        $this->assertTrue(collect($ownersA)->contains(fn ($v) => str_contains((string) $v, 'orga@example.com')));
        $this->assertFalse(collect($ownersA)->contains(fn ($v) => str_contains((string) $v, 'orgb@example.com')));

        app(TenantContext::class)->set($orgB);
        $headersB = $this->templates->headers($this->provider, $orgB);
        $this->assertContains('Org B Field', $headersB);
        $this->assertNotContains('Org A Field', $headersB);
    }

    public function test_unauthorized_users_cannot_download_templates(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.template.csv'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.template.xlsx'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.create'))
            ->assertOk()
            ->assertSee(route('leads.import.template.csv'), false)
            ->assertSee(route('leads.import.template.xlsx'), false);
    }

    public function test_inactive_metadata_fields_are_excluded(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'inactive_field',
            'label' => 'Inactive Field',
            'type' => 'text',
            'status' => 'inactive',
            'published_at' => now(),
        ]);

        $headers = $this->templates->headers($this->provider, $organization);
        $this->assertNotContains('Inactive Field', $headers);
    }
}
