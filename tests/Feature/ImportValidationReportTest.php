<?php

namespace Tests\Feature;

use App\Models\ImportSession;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Import\ImportPlatformService;
use App\Services\Import\ImportValidationReportService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportValidationReportTest extends TestCase
{
    use RefreshDatabase;

    protected ImportPlatformService $imports;

    protected ImportValidationReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->imports = app(ImportPlatformService::class);
        $this->reports = app(ImportValidationReportService::class);
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

    /**
     * Extract the error-table portion of a CSV report (rows after the header line).
     *
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    protected function parseErrorTable(string $csv): array
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");
        $lines = preg_split("/\r\n|\n|\r/", rtrim($csv)) ?: [];

        $headerIndex = null;
        foreach ($lines as $index => $line) {
            $cells = str_getcsv($line);
            if (($cells[0] ?? null) === 'Row Number') {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = str_getcsv($lines[$headerIndex]);
        $rows = [];
        foreach (array_slice($lines, $headerIndex + 1) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function buildInvalidLeadSession(User $user, Organization $organization): ImportSession
    {
        app(TenantContext::class)->set($organization);

        $rep = User::factory()->create(['name' => 'Sales Rep', 'email' => 'rep@example.com']);
        $organization->addMember($rep, 'sales-executive');

        $file = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "Full Name,Email,Phone,Owner,Source,Status\n".
            "Ada Lovelace,ada@example.com,+15550100100,rep@example.com,Website,New\n".
            "Bad Email,not-an-email,+15550100101,rep@example.com,Website,New\n".
            "Bad Owner,owner@example.com,+15550100102,Nobody Here,Website,New\n".
            "Bad Source,src@example.com,+15550100103,rep@example.com,Television,New\n".
            "Bad Status,st@example.com,+15550100104,rep@example.com,Website,NotAStage\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);

        return $this->imports->validate($session, $user);
    }

    public function test_csv_report_has_summary_columns_and_bom(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($user, $organization);

        $csv = $this->reports->toCsvString($session->fresh());

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Import Validation Report', $csv);
        $this->assertStringContainsString('Organization', $csv);
        $this->assertStringContainsString($organization->name, $csv);
        $this->assertStringContainsString('Rows Processed', $csv);
        $this->assertStringContainsString('Rows Valid', $csv);
        $this->assertStringContainsString('Rows Invalid', $csv);
        $this->assertStringContainsString('Total Errors', $csv);

        $table = $this->parseErrorTable($csv);
        $this->assertSame(['Row Number', 'Column', 'Imported Value', 'Error Message'], $table['headers']);
    }

    public function test_report_summary_counts_match_validation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($user, $organization);

        $summary = $this->reports->summary($session->fresh());

        $this->assertSame(5, $summary['rows_processed']);
        $this->assertSame(1, $summary['rows_valid']);
        $this->assertSame(4, $summary['rows_invalid']);
        $this->assertSame($organization->name, $summary['organization']);
        $this->assertGreaterThanOrEqual(4, $summary['total_errors']);
    }

    public function test_report_contains_row_column_value_and_error(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($user, $organization);

        $table = $this->parseErrorTable($this->reports->toCsvString($session->fresh()));

        // Bad email is row 3 (header row 1, first data row 2).
        $emailError = collect($table['rows'])->first(
            fn (array $r) => $r[0] === '3' && $r[1] === 'Email'
        );
        $this->assertNotNull($emailError);
        $this->assertSame('not-an-email', $emailError[2]);
        $this->assertStringContainsString('valid email', $emailError[3]);

        // Unknown owner (entity error) resolves the field key to the "Owner" label.
        $ownerError = collect($table['rows'])->first(
            fn (array $r) => $r[0] === '4' && $r[1] === 'Owner'
        );
        $this->assertNotNull($ownerError);
        $this->assertSame('Nobody Here', $ownerError[2]);
        $this->assertStringContainsString('Unknown owner', $ownerError[3]);

        // Unknown source lookup error.
        $sourceError = collect($table['rows'])->first(
            fn (array $r) => $r[0] === '5' && $r[1] === 'Source'
        );
        $this->assertNotNull($sourceError);
        $this->assertStringContainsString('Unknown lead source', $sourceError[3]);

        // Unknown status lookup error.
        $statusError = collect($table['rows'])->first(
            fn (array $r) => $r[0] === '6' && $r[1] === 'Status'
        );
        $this->assertNotNull($statusError);
        $this->assertStringContainsString('Unknown lead status', $statusError[3]);
    }

    public function test_errors_are_ordered_by_row_then_column(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        // Row 2: two errors (Email + Phone). Row 3: one error (Email).
        $file = UploadedFile::fake()->createWithContent(
            'order.csv',
            "Full Name,Email,Phone\n".
            "Multi Bad,not-an-email,123\n".
            "Also Bad,also-bad,+15550100100\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);

        $rows = $this->reports->buildRows($session->fresh());

        $ordered = array_map(
            static fn (array $r): array => [$r['row_number'], $r['column']],
            $rows
        );

        $sorted = $ordered;
        usort($sorted, static fn ($a, $b) => [$a[0], strtolower($a[1])] <=> [$b[0], strtolower($b[1])]);

        $this->assertSame($sorted, $ordered);

        // Row 2 (Email, Phone) must come before row 3.
        $this->assertSame(2, $ordered[0][0]);
        $this->assertSame(2, $ordered[1][0]);
        $this->assertSame('Email', $ordered[0][1]);
        $this->assertSame('Phone', $ordered[1][1]);
        $this->assertSame(3, $ordered[2][0]);
    }

    public function test_metadata_validation_errors_are_reported(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        MetadataFieldDefinition::query()->create([
            'organization_id' => $organization->id,
            'entity_type' => 'lead',
            'key' => 'age',
            'label' => 'Age',
            'type' => 'number',
            'status' => 'active',
            'published_at' => now(),
            'activated_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'meta.csv',
            "Full Name,Email,Phone,Age\n".
            "Meta User,meta@example.com,+15550100150,not-a-number\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);

        $rows = $this->reports->buildRows($session->fresh());
        $this->assertNotEmpty($rows);

        $ageError = collect($rows)->first(
            fn (array $r) => $r['column'] === 'Age' || str_contains(strtolower($r['error']), 'age')
        );
        $this->assertNotNull($ageError);
    }

    public function test_duplicate_rows_are_reported(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'existing@example.com',
            'status' => 'new',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'dupes.csv',
            "Full Name,Email,Phone\n".
            "Existing,existing@example.com,+15550100151\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);

        $rows = $this->reports->buildRows($session->fresh());
        $duplicate = collect($rows)->first(
            fn (array $r) => str_contains(strtolower($r['error']), 'duplicate')
        );

        $this->assertNotNull($duplicate);
    }

    public function test_report_has_no_errors_when_all_rows_valid(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'valid.csv',
            "Full Name,Email,Phone\n".
            "Good Lead,good@example.com,+15550100152\n"
        );

        $session = $this->imports->upload($organization, 'lead', $file, $user);
        $session = $this->imports->validate($session, $user);

        $this->assertFalse($this->reports->hasErrors($session->fresh()));
        $this->assertSame([], $this->reports->buildRows($session->fresh()));
    }

    public function test_xlsx_report_has_summary_and_errors_sheets(): void
    {
        [$user, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($user, $organization);

        $binary = $this->reports->toXlsxBinary($session->fresh());
        $this->assertNotSame('', $binary);

        $tmp = tempnam(sys_get_temp_dir(), 'report');
        $path = $tmp.'.xlsx';
        @unlink($tmp);
        file_put_contents($path, $binary);

        try {
            $spreadsheet = IOFactory::load($path);
            $names = $spreadsheet->getSheetNames();

            $this->assertContains(ImportValidationReportService::SUMMARY_SHEET, $names);
            $this->assertContains(ImportValidationReportService::ERRORS_SHEET, $names);

            $errors = $spreadsheet->getSheetByName(ImportValidationReportService::ERRORS_SHEET);
            $this->assertSame('Row Number', $errors->getCell([1, 1])->getValue());
            $this->assertSame('Column', $errors->getCell([2, 1])->getValue());
            $this->assertSame('Imported Value', $errors->getCell([3, 1])->getValue());
            $this->assertSame('Error Message', $errors->getCell([4, 1])->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    public function test_download_button_appears_only_when_errors_exist(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();

        // Session with errors -> button visible.
        $errorSession = $this->buildInvalidLeadSession($owner, $organization);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.preview', $errorSession))
            ->assertOk()
            ->assertSee('Download Validation Report')
            ->assertSee(route('leads.import.report', $errorSession), false);

        // Fully valid session -> button hidden.
        $file = UploadedFile::fake()->createWithContent(
            'valid.csv',
            "Full Name,Email,Phone\nGood Lead,good@example.com,+15550100153\n"
        );
        $validSession = $this->imports->upload($organization, 'lead', $file, $owner);
        $validSession = $this->imports->validate($validSession, $owner);

        $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.preview', $validSession))
            ->assertOk()
            ->assertDontSee('Download Validation Report');
    }

    public function test_report_download_route_streams_csv(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($owner, $organization);

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.report', $session));

        $response->assertOk();
        $this->assertStringContainsString('validation_report_'.$session->id.'.csv', (string) $response->headers->get('content-disposition'));

        $table = $this->parseErrorTable($response->streamedContent());
        $this->assertSame(['Row Number', 'Column', 'Imported Value', 'Error Message'], $table['headers']);
        $this->assertNotEmpty($table['rows']);
    }

    public function test_employee_cannot_download_validation_report(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        $session = $this->buildInvalidLeadSession($owner, $organization);

        $employee = User::factory()->create();
        $organization->addMember($employee, 'employee');

        $this->actingAs($employee)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('leads.import.report', $session))
            ->assertForbidden();
    }

    public function test_cross_tenant_report_access_is_denied(): void
    {
        [$ownerA, $orgA] = $this->setupUserWithOrg();
        $sessionA = $this->buildInvalidLeadSession($ownerA, $orgA);

        [$ownerB, $orgB] = $this->setupUserWithOrg();

        $this->actingAs($ownerB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->get(route('leads.import.report', $sessionA))
            ->assertNotFound();
    }

    public function test_customer_import_validation_report(): void
    {
        [$owner, $organization] = $this->setupUserWithOrg();
        app(TenantContext::class)->set($organization);

        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            "Full Name,Email,Phone\n".
            "Bad Customer,not-an-email,123\n"
        );

        $session = $this->imports->upload($organization, 'customer', $file, $owner);
        $session = $this->imports->validate($session, $owner);

        $this->assertTrue($this->reports->hasErrors($session->fresh()));

        $response = $this->actingAs($owner)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.import.report', $session));

        $response->assertOk();
        $table = $this->parseErrorTable($response->streamedContent());
        $this->assertSame(['Row Number', 'Column', 'Imported Value', 'Error Message'], $table['headers']);
        $this->assertNotEmpty($table['rows']);
    }
}
