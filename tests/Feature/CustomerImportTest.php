<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ImportSession;
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

class CustomerImportTest extends TestCase
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

    public function test_customer_adapter_is_registered(): void
    {
        $this->assertTrue($this->imports->registry()->has('customer'));
        $this->assertSame('Customer', $this->imports->registry()->resolve('customer')->entityLabel());
    }

    public function test_imports_valid_csv_through_customer_service(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $assignee = User::factory()->create(['name' => 'Sales Rep', 'email' => 'custrep@example.com']);
        $organization->addMember($assignee, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            "Full Name,Email,Phone,Company,Status,Owner,Source,Customer Type,Notes\n".
            "Ada Lovelace,ada-cust@example.com,+15550100111,Analytical Engines,Active,custrep@example.com,Website,Company,Imported note\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $session = $this->imports->validate($session, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(0, $preview->invalidRows);

        $session = $this->imports->executeImport($session, $user);

        $this->assertSame(ImportSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(1, $session->created_count);

        $customer = Customer::query()->where('organization_id', $organization->id)->first();
        $this->assertNotNull($customer);
        $this->assertSame('Ada Lovelace', $customer->name);
        $this->assertSame('ada-cust@example.com', $customer->email);
        $this->assertSame('active', $customer->status);
        $this->assertSame($assignee->id, $customer->assigned_to);
        $this->assertSame(1, $customer->notes()->count());
        $this->assertStringContainsString('Imported note', $customer->notes()->first()->body);
        $this->assertStringContainsString('Source: Website', $customer->notes()->first()->body);
        $this->assertStringContainsString('Customer Type: Company', $customer->notes()->first()->body);

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

        $tmp = tempnam(sys_get_temp_dir(), 'custimp');
        $xlsxPath = $tmp.'.xlsx';
        @unlink($tmp);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['First Name', 'Last Name', 'Email Address', 'Status'],
            ['Grace', 'Hopper', 'grace-cust@example.com', 'Prospect'],
        ]);
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile(
            $xlsxPath,
            'customers.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $session = $this->imports->validate($session, $user);
        $session = $this->imports->executeImport($session, $user);

        $this->assertSame(1, $session->created_count);
        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'name' => 'Grace Hopper',
            'email' => 'grace-cust@example.com',
            'status' => 'prospect',
        ]);

        @unlink($xlsxPath);
    }

    public function test_metadata_fields_are_imported(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'customer',
            'key' => 'account_tier',
            'label' => 'Account Tier',
            'type' => 'text',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'meta.csv',
            "Full Name,Email,Account Tier\n".
            "Meta Customer,meta-cust@example.com,Gold\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $session = $this->imports->validate($session, $user);
        $session = $this->imports->executeImport($session, $user);

        $customer = Customer::query()->where('email', 'meta-cust@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Gold', $customer->custom_fields['account_tier'] ?? null);
    }

    public function test_owner_resolution_by_name_and_unknown_owner(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $assignee = User::factory()->create(['name' => 'Casey Agent', 'email' => 'casey-cust@example.com']);
        $organization->addMember($assignee, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'owners.csv',
            "Full Name,Email,Owner\n".
            "Named Customer,named-cust@example.com,Casey Agent\n".
            "Bad Owner,badowner-cust@example.com,Nobody Here\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(1, $preview->invalidRows);

        $session = $this->imports->executeImport($session->fresh(), $user);
        $customer = Customer::query()->where('email', 'named-cust@example.com')->first();
        $this->assertSame($assignee->id, $customer?->assigned_to);
        $this->assertNull(Customer::query()->where('email', 'badowner-cust@example.com')->first());
    }

    public function test_duplicate_detection_against_existing_and_file(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'existing-cust@example.com',
            'phone' => '+15550100998',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'dupes.csv',
            "Full Name,Email,Phone\n".
            "Existing,existing-cust@example.com,+15550100002\n".
            "Phone Dup,other-cust@example.com,+15550100998\n".
            "First In File,filedup-cust@example.com,+15550100223\n".
            "Second In File,filedup-cust@example.com,+15550100334\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(1, $preview->validRows);
        $this->assertSame(3, $preview->invalidRows);

        $session = $this->imports->executeImport($session->fresh(), $user);
        $this->assertSame(1, $session->created_count);
        $this->assertSame(3, $session->skipped_count);
        $this->assertSame(1, Customer::query()->where('email', 'filedup-cust@example.com')->count());
    }

    public function test_invalid_email_phone_and_lookup_failures(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            "Full Name,Email,Phone,Status,Source,Customer Type\n".
            "Bad Email,not-an-email,555-0100,Active,Website,Company\n".
            "Bad Phone,ok-cust@example.com,123,Active,Website,Company\n".
            "Bad Status,status-cust@example.com,+15550100444,NotAStatus,Website,Company\n".
            "Bad Source,source-cust@example.com,+15550100445,Active,Television,Company\n".
            "Bad Type,type-cust@example.com,+15550100446,Active,Website,Enterprise\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(0, $preview->validRows);
        $this->assertSame(5, $preview->invalidRows);
        $messages = collect($preview->errors)->pluck('error')->implode(' ');
        $this->assertStringContainsString('valid email', $messages);
        $this->assertStringContainsString('valid phone', $messages);
        $this->assertStringContainsString('Unknown customer status', $messages);
        $this->assertStringContainsString('Unknown customer source', $messages);
        $this->assertStringContainsString('Unknown customer type', $messages);
    }

    public function test_tenant_isolation_for_customer_import(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [$userB, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        Customer::factory()->create([
            'organization_id' => $orgA->id,
            'email' => 'shared-cust@example.com',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'tenant.csv',
            "Full Name,Email\nShared,shared-cust@example.com\n"
        );

        $sessionA = $this->imports->upload($orgA, 'customer', $file, $userA);
        $previewA = $this->imports->preview($sessionA, $userA);
        $this->assertSame(0, $previewA->validRows);

        app(TenantContext::class)->set($orgB);
        $sessionB = $this->imports->upload($orgB, 'customer', $file, $userB);
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
            ->get(route('customers.import.create'))
            ->assertForbidden();

        $csv = UploadedFile::fake()->createWithContent(
            'http.csv',
            "Full Name,Email\nHttp Customer,http-cust@example.com\n"
        );

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.import.store'), ['file' => $csv]);

        $session = ImportSession::query()
            ->where('organization_id', $organization->id)
            ->where('entity_type', 'customer')
            ->first();
        $this->assertNotNull($session);
        $response->assertRedirect(route('customers.import.preview', $session));

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.import.execute', $session))
            ->assertRedirect(route('customers.import.summary', $session));

        $this->assertDatabaseHas('customers', [
            'organization_id' => $organization->id,
            'email' => 'http-cust@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(route('customers.import.create'), false);
    }
}
