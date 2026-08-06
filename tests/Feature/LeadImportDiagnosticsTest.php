<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Jobs\ProcessImportSessionJob;
use App\Models\Employee;
use App\Models\ImportSession;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadImportDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected ImportPlatformService $imports;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->imports = app(ImportPlatformService::class);
    }

    public function test_valid_rows_support_all_owner_identifiers_and_blank_owner(): void
    {
        [$organization, $owner, , , $salesA, $salesB] = $this->createImportTeam();
        app(TenantContext::class)->set($organization);

        $salesAEmployee = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $salesA->id)
            ->sole();
        $salesBEmployee = Employee::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $salesB->id)
            ->sole();

        $csv = implode("\n", [
            'Full Name,Email,Phone,Owner,Source,Status,State,Country',
            'Blank Owner,blank@example.com,+15551000001,,Website,New,California,United States',
            "Email Owner,email-owner@example.com,+15551000002,{$salesA->email},Referral,Contacted,Texas,United States",
            "User Name Owner,name-owner@example.com,+15551000003,{$salesB->name},Google Ads,Qualified,Ontario,Canada",
            "Employee Name Owner,employee-owner@example.com,+15551000004,{$salesAEmployee->full_name},Website,New,Delhi,India",
            "Employee Code Owner,code-owner@example.com,+15551000005,{$salesBEmployee->employee_code},Website,New,Maharashtra,India",
            "User ID Owner,id-owner@example.com,+15551000006,{$salesA->id},Website,New,Queensland,Australia",
            '',
        ]);

        $session = $this->uploadValidateExecute($organization, $owner, $csv);

        $this->assertSame(6, $session->created_count);
        $this->assertSame(0, $session->failed_count);
        $this->assertNull(Lead::query()->where('email', 'blank@example.com')->sole()->assigned_to);
        $this->assertSame($salesA->id, Lead::query()->where('email', 'email-owner@example.com')->sole()->assigned_to);
        $this->assertSame($salesB->id, Lead::query()->where('email', 'name-owner@example.com')->sole()->assigned_to);
        $this->assertSame($salesA->id, Lead::query()->where('email', 'employee-owner@example.com')->sole()->assigned_to);
        $this->assertSame($salesB->id, Lead::query()->where('email', 'code-owner@example.com')->sole()->assigned_to);
        $this->assertSame($salesA->id, Lead::query()->where('email', 'id-owner@example.com')->sole()->assigned_to);
        $this->assertDatabaseHas('leads', [
            'email' => 'blank@example.com',
            'state' => 'California',
            'country' => 'United States',
            'created_by' => $owner->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_invalid_rows_have_explicit_reasons_and_valid_rows_continue(): void
    {
        [$organization, $owner, , , $salesA] = $this->createImportTeam();
        app(TenantContext::class)->set($organization);

        $salesA->update(['account_status' => UserAccountStatus::Disabled]);
        $foreignOrganization = Organization::factory()->create();
        $foreignUser = User::factory()->create(['email' => 'foreign@example.com']);
        $foreignOrganization->addMember($foreignUser, 'sales-executive');

        $csv = implode("\n", [
            'Full Name,Email,Phone,Owner,Source,Status',
            'Valid Lead,valid@example.com,+15552000001,,Website,New',
            'Missing Phone,no-phone@example.com,,,Website,New',
            ',missing-name@example.com,+15552000003,,Website,New',
            "Inactive Owner,inactive@example.com,+15552000004,{$salesA->email},Website,New",
            'Foreign Owner,foreign-owner@example.com,+15552000005,foreign@example.com,Website,New',
            'Unknown Owner,unknown-owner@example.com,+15552000006,Nobody Here,Website,New',
            'Bad Status,bad-status@example.com,+15552000007,,Website,Impossible',
            'Bad Source,bad-source@example.com,+15552000008,,Television,New',
            '',
        ]);

        $file = UploadedFile::fake()->createWithContent('invalid-leads.csv', $csv);
        $session = $this->imports->upload($organization, 'lead', $file, $owner);
        $session = $this->imports->validate($session, $owner);

        $this->assertSame(1, $session->validation_summary['valid_rows']);
        $this->assertSame(7, $session->validation_summary['invalid_rows']);

        $session = $this->imports->executeImport($session, $owner);
        $messages = collect($session->validation_summary['execution_errors'])->pluck('error')->implode(' ');

        $this->assertSame(1, $session->created_count);
        $this->assertSame(7, $session->failed_count);
        $this->assertStringContainsString('Phone is required', $messages);
        $this->assertStringContainsString('Full Name', $messages);
        $this->assertStringContainsString('inactive or locked', $messages);
        $this->assertStringContainsString('another organization', $messages);
        $this->assertStringContainsString('Unknown owner', $messages);
        $this->assertStringContainsString('Unknown lead status', $messages);
        $this->assertStringContainsString('Unknown lead source', $messages);
        $this->assertDatabaseHas('leads', ['email' => 'valid@example.com']);
    }

    public function test_database_row_failure_is_logged_in_summary_and_remaining_rows_continue(): void
    {
        [$organization, $owner] = $this->createImportTeam();
        app(TenantContext::class)->set($organization);

        $tooLongCompany = str_repeat('X', 300);
        $csv = implode("\n", [
            'Full Name,Email,Phone,Company',
            "Database Failure,db-failure@example.com,+15553000001,{$tooLongCompany}",
            'Still Imports,continues@example.com,+15553000002,Nova',
            '',
        ]);

        $session = $this->uploadValidateExecute($organization, $owner, $csv);
        $summary = $session->validation_summary['execution_summary'];

        $this->assertSame(1, $session->created_count);
        $this->assertSame(1, $session->failed_count);
        $this->assertSame(1, $summary['database_errors']);
        $this->assertCount(1, $session->validation_summary['execution_errors']);
        $this->assertDatabaseMissing('leads', ['email' => 'db-failure@example.com']);
        $this->assertDatabaseHas('leads', ['email' => 'continues@example.com']);
        $this->assertStringContainsString('db-failure@example.com', $this->imports->errorReportCsv($session));
    }

    public function test_duplicate_strategies_skip_update_and_create(): void
    {
        [$organization, $owner] = $this->createImportTeam();
        app(TenantContext::class)->set($organization);

        $existing = Lead::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $owner->id,
            'name' => 'Existing Lead',
            'email' => 'duplicate@example.com',
            'phone' => '+15554000001',
            'company' => 'Old Company',
            'status' => 'new',
        ]);

        $skip = $this->uploadValidateExecute(
            $organization,
            $owner,
            "Full Name,Email,Phone,Company\nSkip Lead,duplicate@example.com,+15554000001,Skipped\n",
            'skip',
        );
        $this->assertSame(1, $skip->skipped_count);

        $update = $this->uploadValidateExecute(
            $organization,
            $owner,
            "Full Name,Email,Phone,Company\nUpdated Lead,duplicate@example.com,+15554000001,Updated Company\n",
            'update',
        );
        $this->assertSame(1, $update->updated_count);
        $this->assertSame('Updated Company', $existing->fresh()->company);

        $create = $this->uploadValidateExecute(
            $organization,
            $owner,
            "Full Name,Email,Phone,Company\nCreated Duplicate,duplicate@example.com,+15554000001,New Company\n",
            'create',
        );
        $this->assertSame(1, $create->created_count);
        $this->assertSame(2, Lead::query()->where('email', 'duplicate@example.com')->count());
    }

    public function test_large_import_is_queued_then_processes_all_rows(): void
    {
        [$organization, $owner] = $this->createImportTeam();
        app(TenantContext::class)->set($organization);
        Queue::fake();
        config(['import.queue_threshold_rows' => 100]);

        $lines = ['Full Name,Email,Phone'];
        for ($index = 1; $index <= 1001; $index++) {
            $lines[] = "Lead {$index},lead{$index}@example.com,+1555".str_pad((string) $index, 7, '0', STR_PAD_LEFT);
        }

        $file = UploadedFile::fake()->createWithContent('large.csv', implode("\n", $lines)."\n");
        $session = $this->imports->upload($organization, 'lead', $file, $owner);
        $session = $this->imports->validate($session, $owner);
        $session = $this->imports->startImport($session, $owner);

        $this->assertSame(ImportSession::STATUS_QUEUED, $session->status);
        Queue::assertPushed(ProcessImportSessionJob::class);

        $job = new ProcessImportSessionJob($session->id, $owner->id);
        $job->handle($this->imports, app(TenantContext::class));

        $session->refresh();
        $this->assertSame(ImportSession::STATUS_COMPLETED, $session->status);
        $this->assertSame(1001, $session->created_count);
        $this->assertSame(1001, Lead::query()->where('organization_id', $organization->id)->count());
        $this->assertGreaterThan(0, $session->validation_summary['execution_summary']['processing_time_ms']);
    }

    /**
     * @return array{Organization, User, User, User, User, User}
     */
    protected function createImportTeam(): array
    {
        $organization = Organization::factory()->create();
        $definitions = [
            ['Owner User', 'owner@example.com', 'organization-owner'],
            ['HR User', 'hr@example.com', 'hr'],
            ['Sales Manager', 'manager@example.com', 'manager'],
            ['Sales Executive A', 'sales-a@example.com', 'sales-executive'],
            ['Sales Executive B', 'sales-b@example.com', 'sales-executive'],
        ];
        $users = [];

        foreach ($definitions as $index => [$name, $email, $role]) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'account_status' => UserAccountStatus::Active,
            ]);
            $organization->addMember($user, $role);
            Employee::factory()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'employee_code' => 'EMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'first_name' => explode(' ', $name, 2)[0],
                'last_name' => explode(' ', $name, 2)[1] ?? null,
                'email' => $email,
                'status' => 'active',
            ]);
            $users[] = $user;
        }

        return [$organization, ...$users];
    }

    protected function uploadValidateExecute(
        Organization $organization,
        User $owner,
        string $csv,
        string $duplicateStrategy = 'skip',
    ): ImportSession {
        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);
        $session = $this->imports->upload($organization, 'lead', $file, $owner);
        $session->forceFill([
            'metadata' => ['duplicate_strategy' => $duplicateStrategy],
        ])->save();
        $session = $this->imports->validate($session->fresh(), $owner);

        return $this->imports->executeImport($session, $owner);
    }
}
