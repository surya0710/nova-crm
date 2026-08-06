<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ImportSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportEntityRegistry;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\Support\FakeImportableEntity;
use Tests\TestCase;

class ImportPlatformSessionTest extends TestCase
{
    use RefreshDatabase;

    protected ImportPlatformService $imports;

    protected ImportEntityRegistry $registry;

    protected FakeImportableEntity $entity;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->registry = app(ImportEntityRegistry::class);
        $this->entity = new FakeImportableEntity('demo');
        $this->registry->register($this->entity);
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

    public function test_upload_creates_session_and_audit_log(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'contacts.csv',
            "Email,Full Name\nada@example.com,Ada Lovelace\n"
        );

        $session = $this->imports->upload($organization, 'demo', $file, $user);

        $this->assertSame(ImportSession::STATUS_UPLOADED, $session->status);
        $this->assertSame($organization->id, $session->organization_id);
        $this->assertSame('demo', $session->entity_type);
        $this->assertSame('contacts.csv', $session->original_filename);
        $this->assertSame($user->id, $session->uploaded_by);
        $this->assertNotNull($session->started_at);
        Storage::disk('local')->assertExists($session->stored_path);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'auditable_type' => $session->getMorphClass(),
            'auditable_id' => $session->id,
            'event' => 'uploaded',
        ]);
    }

    public function test_validation_preview_and_error_report_lifecycle(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'contacts.csv',
            "EMAIL,Full Name,Phone\nnot-an-email,Jane,555-0100\nok@example.com,Ada,+15550100100\n"
        );

        $session = $this->imports->upload($organization, 'demo', $file, $user);
        $session = $this->imports->validate($session, $user);

        $this->assertSame(ImportSession::STATUS_READY, $session->status);
        $this->assertSame(2, $session->total_rows);
        $this->assertSame(1, $session->failed_count);
        $this->assertSame('EMAIL', $session->column_mapping['email']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $session->id,
            'event' => 'validated',
        ]);

        $preview = $this->imports->preview($session, $user);

        $this->assertSame(['EMAIL', 'Full Name', 'Phone'], $preview->detectedColumns);
        $this->assertSame(1, $preview->validRows);
        $this->assertSame(1, $preview->invalidRows);
        $this->assertNotEmpty($preview->errors);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $session->id,
            'event' => 'preview_generated',
        ]);

        $csv = $this->imports->errorReportCsv($session);
        $this->assertStringContainsString('row_number,column,field,error,original_value', $csv);
        $this->assertStringContainsString('not-an-email', $csv);
    }

    public function test_xlsx_upload_and_validation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $tmp = tempnam(sys_get_temp_dir(), 'imp');
        $xlsxPath = $tmp.'.xlsx';
        @unlink($tmp);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Email Address', 'Name'],
            ['grace@example.com', 'Grace Hopper'],
        ]);
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile($xlsxPath, 'people.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $session = $this->imports->upload($organization, 'demo', $file, $user);
        $session = $this->imports->validate($session, $user);
        $preview = $this->imports->preview($session, $user);

        $this->assertSame(ImportSession::STATUS_READY, $session->status);
        $this->assertSame(1, $preview->validRows);
        $this->assertSame(0, $preview->invalidRows);

        @unlink($xlsxPath);
    }

    public function test_cancel_transitions_to_cancelled(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent('a.csv', "Email,Full Name\na@b.com,A\n");
        $session = $this->imports->upload($organization, 'demo', $file, $user);
        $session = $this->imports->cancel($session, $user);

        $this->assertSame(ImportSession::STATUS_CANCELLED, $session->status);
        $this->assertNotNull($session->completed_at);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $session->id,
            'event' => 'cancelled',
        ]);
    }

    public function test_malformed_file_marks_session_failed(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent('broken.xlsx', 'not-xlsx-content');

        $session = $this->imports->upload($organization, 'demo', $file, $user);

        try {
            $this->imports->validate($session, $user);
            $this->fail('Expected RuntimeException for malformed xlsx.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Unable to read XLSX', $e->getMessage());
        }

        $session->refresh();
        $this->assertSame(ImportSession::STATUS_FAILED, $session->status);
        $this->assertNotNull($session->last_error);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $session->id,
            'event' => 'validation_failed',
        ]);
    }

    public function test_status_transition_rules_are_enforced(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid import session status transition');

        ImportSession::assertValidTransition(
            ImportSession::STATUS_COMPLETED,
            ImportSession::STATUS_READY
        );
    }

    public function test_tenant_isolation_prevents_cross_organization_access(): void
    {
        [$userA, $orgA] = $this->setupUserWithOrg();
        [, $orgB] = $this->setupUserWithOrg();

        app(TenantContext::class)->set($orgA);
        $file = UploadedFile::fake()->createWithContent('a.csv', "Email,Full Name\na@b.com,A\n");
        $session = $this->imports->upload($orgA, 'demo', $file, $userA);

        app(TenantContext::class)->set($orgB);

        $this->assertNull($this->imports->findForOrganization($orgB, $session->id));
        $this->assertNotNull($this->imports->findForOrganization($orgA, $session->id));

        $this->assertSame(
            0,
            ImportSession::query()->where('organization_id', $orgB->id)->count()
        );
    }

    public function test_unauthorized_employee_lacks_import_permissions(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg('organization-owner');
        [$employee] = (function () use ($organization) {
            $user = User::factory()->create();
            $organization->addMember($user, 'employee');

            return [$user];
        })();

        $this->assertTrue($owner->hasPermission('imports.view', $organization));
        $this->assertTrue($owner->hasPermission('imports.create', $organization));
        $this->assertTrue($owner->hasPermission('imports.manage', $organization));

        $this->assertFalse($employee->hasPermission('imports.view', $organization));
        $this->assertFalse($employee->hasPermission('imports.create', $organization));
        $this->assertFalse($employee->hasPermission('imports.manage', $organization));
    }

    public function test_manager_has_import_permissions(): void
    {
        [$manager, $organization] = $this->setupUserWithOrg('manager');

        $this->assertTrue($manager->hasPermission('imports.view', $organization));
        $this->assertTrue($manager->hasPermission('imports.create', $organization));
        $this->assertTrue($manager->hasPermission('imports.manage', $organization));
        $this->assertTrue($manager->hasPermission('imports.crm', $organization));
        $this->assertTrue($manager->hasPermission('imports.hrms', $organization));
        $this->assertTrue($manager->hasPermission('imports.projects', $organization));
        $this->assertTrue($manager->hasPermission('imports.administration', $organization));
    }

    public function test_unknown_entity_type_is_rejected(): void
    {
        [, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Importable entity [not_a_real_entity] is not registered.');

        $file = UploadedFile::fake()->createWithContent('a.csv', "Email\na@b.com\n");
        $this->imports->upload($organization, 'not_a_real_entity', $file);
    }

    public function test_persist_callback_is_not_invoked_by_foundation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'ok.csv',
            "Email,Full Name\nok@example.com,Ok User\n"
        );

        $session = $this->imports->upload($organization, 'demo', $file, $user);
        $this->imports->validate($session, $user);
        $this->imports->preview($session, $user);

        $this->assertSame([], $this->entity->persisted);
        $this->assertSame(0, AuditLog::query()->where('event', 'imported')->count());
    }

    public function test_registry_catalog_exposes_registered_entities(): void
    {
        $catalog = collect($this->registry->catalog())->keyBy('type');

        $this->assertTrue($this->registry->has('demo'));
        $this->assertSame('Demo Entity', $catalog['demo']['label']);
        $this->assertGreaterThan(0, $catalog['demo']['field_count']);
    }
}
