<?php

namespace Tests\Feature;

use App\Events\EmployeeDocumentUploaded;
use App\Events\EmployeeDocumentVerified;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrmsEmployeeDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('hrms.documents.disk', 'local'));
    }

    public function test_hr_can_upload_employee_document(): void
    {
        Event::fake([EmployeeDocumentUploaded::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $response = $this->actingAs($hr)->withSession($session)->post(
            route('hrms.employees.documents.store', $employee),
            [
                'category' => 'passport',
                'title' => 'Passport Copy',
                'expires_at' => now()->addYear()->toDateString(),
                'file' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
            ],
        );

        $document = EmployeeDocument::query()->firstOrFail();
        $response->assertRedirect(route('hrms.employees.documents.show', [$employee, $document]));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'category' => 'passport',
            'title' => 'Passport Copy',
            'verification_status' => 'pending',
        ]);
        $this->assertDatabaseHas('employee_document_versions', [
            'employee_document_id' => $document->id,
            'version_no' => 1,
            'original_name' => 'passport.pdf',
        ]);
        $this->assertSame($document->versions()->first()->id, $document->current_version_id);

        Event::assertDispatched(EmployeeDocumentUploaded::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_uploaded']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_version_created']);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.employees.documents.store', $employee), [
                'category' => 'passport',
                'title' => 'Bad File',
                'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
            ])->assertSessionHasErrors('file');
    }

    public function test_upload_rejects_invalid_category(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.employees.documents.store', $employee), [
                'category' => 'not_a_category',
                'title' => 'Test',
                'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])->assertSessionHasErrors('category');
    }

    public function test_upload_rejects_oversized_file(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $maxKb = (int) config('hrms.documents.max_size_kb', 10240);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.employees.documents.store', $employee), [
                'category' => 'pan',
                'title' => 'PAN',
                'file' => UploadedFile::fake()->create('pan.pdf', $maxKb + 1, 'application/pdf'),
            ])->assertSessionHasErrors('file');
    }

    public function test_uploading_new_version_increments_version_and_preserves_history(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.store', $employee), [
            'category' => 'aadhaar',
            'title' => 'Aadhaar',
            'file' => UploadedFile::fake()->create('aadhaar-v1.pdf', 50, 'application/pdf'),
        ]);

        $document = EmployeeDocument::query()->firstOrFail();
        $firstVersionId = $document->current_version_id;

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.documents.update', [$employee, $document]), [
            'title' => 'Aadhaar',
            'category' => 'aadhaar',
            'file' => UploadedFile::fake()->create('aadhaar-v2.pdf', 60, 'application/pdf'),
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame(2, $document->versions()->count());
        $this->assertNotSame($firstVersionId, $document->current_version_id);
        $this->assertSame(2, $document->currentVersion->version_no);
        $this->assertDatabaseHas('employee_document_versions', [
            'employee_document_id' => $document->id,
            'version_no' => 1,
            'original_name' => 'aadhaar-v1.pdf',
        ]);
    }

    public function test_historical_version_can_be_downloaded(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.store', $employee), [
            'category' => 'pan',
            'title' => 'PAN Card',
            'file' => UploadedFile::fake()->create('pan-v1.pdf', 50, 'application/pdf'),
        ]);

        $document = EmployeeDocument::query()->firstOrFail();
        $firstVersion = $document->currentVersion;

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.documents.update', [$employee, $document]), [
            'title' => 'PAN Card',
            'category' => 'pan',
            'file' => UploadedFile::fake()->create('pan-v2.pdf', 60, 'application/pdf'),
        ]);

        $this->actingAs($hr)->withSession($session)
            ->get(route('hrms.employees.documents.download', [$employee, $document, 'version' => $firstVersion->id]))
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_download_document(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $document = $this->seedDocumentWithFile($organization, $employee);

        $outsider = User::factory()->create();
        $organization->addMember($outsider, 'employee');

        $this->actingAs($outsider)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.employees.documents.download', [$employee, $document]))
            ->assertForbidden();
    }

    public function test_cross_organization_document_access_is_forbidden(): void
    {
        [$orgA, $hrA] = $this->organizationWithHrUser();
        [$orgB] = $this->organizationWithHrUser();
        $employeeB = Employee::factory()->create(['organization_id' => $orgB->id]);
        $documentB = $this->seedDocumentWithFile($orgB, $employeeB);

        $this->actingAs($hrA)->withSession(['current_organization_id' => $orgA->id])
            ->get(route('hrms.employees.documents.show', [$employeeB, $documentB]))
            ->assertForbidden();
    }

    public function test_document_must_belong_to_employee_route(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employeeA = Employee::factory()->create(['organization_id' => $organization->id]);
        $employeeB = Employee::factory()->create(['organization_id' => $organization->id]);
        $documentB = $this->seedDocumentWithFile($organization, $employeeB);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.employees.documents.show', [$employeeA, $documentB]))
            ->assertNotFound();
    }

    public function test_hr_can_verify_and_reject_document(): void
    {
        Event::fake([EmployeeDocumentVerified::class]);

        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $document = $this->seedDocumentWithFile($organization, $employee);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.verify', [$employee, $document]), [
            'verification_status' => 'verified',
            'verification_notes' => 'Matches records',
        ])->assertRedirect();

        $document->refresh();
        $this->assertSame('verified', $document->verification_status);
        $this->assertSame($hr->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);

        Event::assertDispatched(EmployeeDocumentVerified::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_verified']);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.verify', [$employee, $document]), [
            'verification_status' => 'rejected',
            'verification_notes' => 'Blurry scan',
        ])->assertRedirect();

        $this->assertSame('rejected', $document->fresh()->verification_status);
    }

    public function test_expiry_date_can_be_updated_and_expired_status_detected(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $document = $this->seedDocumentWithFile($organization, $employee);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.documents.update', [$employee, $document]), [
            'title' => $document->title,
            'category' => $document->category,
            'expires_at' => now()->subDay()->toDateString(),
        ])->assertRedirect();

        $document->refresh();
        $this->assertTrue($document->isExpired());
        $this->assertFalse($document->isExpiringSoon());
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_updated']);
    }

    public function test_expiring_soon_is_calculated_from_config(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $document = $this->seedDocumentWithFile($organization, $employee);
        $days = (int) config('hrms.documents.expiring_soon_days', 30);

        $document->update(['expires_at' => now()->addDays($days - 1)]);
        $this->assertTrue($document->fresh()->isExpiringSoon());
        $this->assertFalse($document->fresh()->isExpired());

        $document->update(['expires_at' => now()->addDays($days + 10)]);
        $this->assertFalse($document->fresh()->isExpiringSoon());
    }

    public function test_delete_document_writes_audit_entry(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $document = $this->seedDocumentWithFile($organization, $employee);

        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->delete(route('hrms.employees.documents.destroy', [$employee, $document]))
            ->assertRedirect(route('hrms.employees.documents.index', $employee));

        $this->assertSoftDeleted('employee_documents', ['id' => $document->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_deleted']);
    }

    public function test_manager_without_documents_permission_cannot_upload(): void
    {
        [$organization] = $this->organizationWithHrUser();
        $manager = User::factory()->create();
        $organization->addMember($manager, 'manager');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($manager)->withSession(['current_organization_id' => $organization->id])
            ->get(route('hrms.employees.documents.create', $employee))
            ->assertForbidden();
    }

    public function test_restore_version_sets_current_version(): void
    {
        [$organization, $hr] = $this->organizationWithHrUser();
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $session = ['current_organization_id' => $organization->id];

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.store', $employee), [
            'category' => 'passport',
            'title' => 'Passport',
            'file' => UploadedFile::fake()->create('passport-v1.pdf', 50, 'application/pdf'),
        ]);

        $document = EmployeeDocument::query()->firstOrFail();
        $firstVersionId = $document->current_version_id;

        $this->actingAs($hr)->withSession($session)->put(route('hrms.employees.documents.update', [$employee, $document]), [
            'title' => 'Passport',
            'category' => 'passport',
            'file' => UploadedFile::fake()->create('passport-v2.pdf', 60, 'application/pdf'),
        ]);

        $this->actingAs($hr)->withSession($session)->post(route('hrms.employees.documents.restore-version', [$employee, $document]), [
            'version_id' => $firstVersionId,
        ])->assertRedirect();

        $this->assertSame($firstVersionId, $document->fresh()->current_version_id);
        $this->assertDatabaseHas('audit_logs', ['event' => 'employee_document_version_restored']);
    }

    private function seedDocumentWithFile(Organization $organization, Employee $employee): EmployeeDocument
    {
        $hr = $organization->users()->first();
        $this->actingAs($hr)->withSession(['current_organization_id' => $organization->id])
            ->post(route('hrms.employees.documents.store', $employee), [
                'category' => 'pan',
                'title' => 'PAN',
                'file' => UploadedFile::fake()->create('pan.pdf', 50, 'application/pdf'),
            ]);

        return EmployeeDocument::query()->firstOrFail();
    }

    /** @return array{0: Organization, 1: User} */
    private function organizationWithHrUser(): array
    {
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $user = User::factory()->create();
        $organization->addMember($user, 'hr');

        return [$organization, $user];
    }
}
