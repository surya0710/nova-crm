<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ImportSession;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LeadImportTest extends TestCase
{
    use RefreshDatabase;

    protected ImportPlatformService $imports;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->imports = app(ImportPlatformService::class);
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

    public function test_lead_adapter_is_registered(): void
    {
        $this->assertTrue($this->imports->registry()->has('lead'));
        $this->assertSame('Lead', $this->imports->registry()->resolve('lead')->entityLabel());
    }

    public function test_imports_valid_csv_through_lead_service(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $assignee = User::factory()->create(['name' => 'Sales Rep', 'email' => 'rep@example.com']);
        $organization->addMember($assignee, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "Full Name,Email,Phone,Company,Source,Status,Owner,Notes\n".
            "Ada Lovelace,ada@example.com,+15550100100,Analytical Engines,Website,New,rep@example.com,Imported note\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(0, $preview->invalidRows);

        $session = $this->imports->executeImport($session, $user);

        $this->assertSame(ImportSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(1, $session->created_count);
        $this->assertSame(0, $session->failed_count);

        $lead = Lead::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame('Ada Lovelace', $lead->name);
        $this->assertSame('ada@example.com', $lead->email);
        $this->assertSame('website', $lead->source);
        $this->assertSame('new', $lead->status);
        $this->assertSame($assignee->id, $lead->assigned_to);
        $this->assertSame(1, $lead->notes()->count());
        $this->assertSame('Imported note', $lead->notes()->first()->body);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $session->getMorphClass(),
            'auditable_id' => $session->id,
            'event' => 'import_started',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $session->getMorphClass(),
            'auditable_id' => $session->id,
            'event' => 'import_completed',
        ]);
    }

    public function test_imports_valid_xlsx(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $tmp = tempnam(sys_get_temp_dir(), 'leadimp');
        $xlsxPath = $tmp.'.xlsx';
        @unlink($tmp);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['First Name', 'Last Name', 'Email Address', 'Source'],
            ['Grace', 'Hopper', 'grace@example.com', 'Referral'],
        ]);
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile(
            $xlsxPath,
            'leads.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);
        $session = $this->imports->executeImport($session, $user);

        $this->assertSame(1, $session->created_count);
        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'source' => 'referral',
        ]);

        @unlink($xlsxPath);
    }

    public function test_metadata_fields_are_imported(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'visa_interest',
            'label' => 'Visa Interest',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'meta.csv',
            "Full Name,Email,Visa Interest\n".
            "Meta User,meta@example.com,Student Visa\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);
        $this->assertSame(ImportSession::STATUS_READY, $session->status);

        $session = $this->imports->executeImport($session, $user);
        $lead = Lead::query()->where('email', 'meta@example.com')->first();

        $this->assertNotNull($lead);
        $this->assertSame('Student Visa', $lead->custom_fields['visa_interest'] ?? null);
    }

    public function test_owner_resolution_by_name_and_unknown_owner(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $assignee = User::factory()->create(['name' => 'Casey Agent', 'email' => 'casey@example.com']);
        $organization->addMember($assignee, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'owners.csv',
            "Full Name,Email,Owner\n".
            "Named Lead,named@example.com,Casey Agent\n".
            "Bad Owner,badowner@example.com,Nobody Here\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(1, $preview->invalidRows);
        $this->assertTrue(
            collect($preview->errors)->contains(fn ($e) => str_contains($e['error'], 'Unknown owner'))
        );

        $session = $this->imports->executeImport($session, $user);
        $lead = Lead::query()->where('email', 'named@example.com')->first();
        $this->assertSame($assignee->id, $lead?->assigned_to);
        $this->assertNull(Lead::query()->where('email', 'badowner@example.com')->first());
    }

    public function test_source_and_status_lookup_resolution(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'lookups.csv',
            "Full Name,Email,Source,Status\n".
            "Ok Lead,oklookup@example.com,Google Ads,Contacted\n".
            "Bad Source,badsource@example.com,Television,New\n".
            "Bad Status,badstatus@example.com,Website,Not A Stage\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(2, $preview->invalidRows);

        $session = $this->imports->executeImport($session->fresh(), $user);
        $lead = Lead::query()->where('email', 'oklookup@example.com')->first();
        $this->assertSame('google_ads', $lead?->source);
        $this->assertSame('contacted', $lead?->status);
    }

    public function test_duplicate_detection_against_existing_and_file(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'existing@example.com',
            'phone' => '+15550100999',
            'status' => 'new',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'dupes.csv',
            "Full Name,Email,Phone\n".
            "Existing,existing@example.com,+15550100001\n".
            "Phone Dup,other@example.com,+15550100999\n".
            "First In File,filedup@example.com,+15550100222\n".
            "Second In File,filedup@example.com,+15550100333\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(3, $preview->invalidRows);
        $this->assertGreaterThanOrEqual(3, (int) ($session->fresh()->validation_summary['duplicate_rows'] ?? 0));

        $session = $this->imports->executeImport($session->fresh(), $user);
        $this->assertSame(1, $session->created_count);
        $this->assertSame(3, $session->skipped_count);
        $this->assertSame(1, Lead::query()->where('email', 'filedup@example.com')->count());
    }

    public function test_invalid_email_and_phone_fail_validation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            "Full Name,Email,Phone\n".
            "Bad Email,not-an-email,555-0100\n".
            "Bad Phone,ok@example.com,123\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(0, $preview->validRows);
        $this->assertSame(2, $preview->invalidRows);
        $messages = collect($preview->errors)->pluck('error')->implode(' ');
        $this->assertStringContainsString('valid email', $messages);
        $this->assertStringContainsString('valid phone', $messages);
    }

    public function test_tenant_isolation_for_lead_import_sessions_and_duplicates(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        Lead::factory()->create([
            'organization_id' => $orgA->id,
            'email' => 'shared@example.com',
            'status' => 'new',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'tenant.csv',
            "Full Name,Email\nShared,shared@example.com\n"
        );

        $sessionA = $this->imports->upload($orgA, 'lead', $file, $userA);
        $previewA = $this->imports->preview($sessionA, $userA);
        $this->assertSame(0, $previewA->validRows);

        app(TenantContext::class)->set($orgB);
        $sessionB = $this->imports->upload($orgB, 'lead', $file, $userB);
        $previewB = $this->imports->preview($sessionB, $userB);
        $this->assertSame(1, $previewB->validRows);

        $this->assertNull($this->imports->findForOrganization($orgB, $sessionA->id));
        $this->assertNotNull($this->imports->findForOrganization($orgA, $sessionA->id));
    }

    public function test_http_import_workflow_and_unauthorized_access(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.create'))
            ->assertForbidden();

        $csv = UploadedFile::fake()->createWithContent(
            'http.csv',
            "Full Name,Email\nHttp Lead,http@example.com\n"
        );

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.import.store'), ['file' => $csv]);

        $session = ImportSession::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($session);
        $response->assertRedirect(route('leads.import.preview', $session));

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.preview', $session))
            ->assertOk()
            ->assertSee('Http Lead');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('leads.import.execute', $session))
            ->assertRedirect(route('leads.import.summary', $session));

        $this->assertDatabaseHas('leads', [
            'organization_id' => $organization->id,
            'email' => 'http@example.com',
            'source' => 'import',
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.summary', $session->fresh()))
            ->assertOk()
            ->assertSee('Created');

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee(route('leads.import.create'), false);
    }

    public function test_organization_alias_maps_to_company(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'company.csv',
            "Full Name,Email,Organization\n".
            "Org Lead,orglead@example.com,Nova Corp\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);
        $session = $this->imports->executeImport($session, $user);

        $this->assertDatabaseHas('leads', [
            'email' => 'orglead@example.com',
            'company' => 'Nova Corp',
        ]);
    }
}
