<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ImportSession;
use App\Services\Import\ImportPlatformService;
use App\Services\Import\ImportValidationReportService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    public function __construct(
        protected ImportPlatformService $imports,
        protected ImportValidationReportService $validationReports,
        protected TenantContext $tenant,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Customer::class);
        abort_unless($this->tenant->get(), 404);
        abort_unless(auth()->user()?->hasPermission('imports.create', $this->tenant->get()), 403);

        return view('imports.customers.create', [
            'organization' => $this->tenant->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);
        $organization = $this->tenant->get();
        abort_unless($organization, 404);
        abort_unless($request->user()?->hasPermission('imports.create', $organization), 403);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('import.max_upload_kilobytes', 10240),
                'mimes:csv,txt,xlsx',
            ],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        $session = $this->imports->upload($organization, 'customer', $file, $request->user());
        $session = $this->imports->validate($session, $request->user());

        return redirect()
            ->route('customers.import.preview', $session)
            ->with('status', 'import-uploaded');
    }

    public function preview(ImportSession $session): View
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);

        $preview = $this->imports->preview($session, auth()->user());

        return view('imports.customers.preview', [
            'session' => $session->fresh(),
            'preview' => $preview,
            'organization' => $this->tenant->get(),
        ]);
    }

    public function execute(ImportSession $session): RedirectResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);
        abort_unless(auth()->user()?->hasPermission('imports.create', $this->tenant->get()), 403);

        $session = $this->imports->executeImport($session, auth()->user());

        return redirect()
            ->route('customers.import.summary', $session)
            ->with('status', 'import-completed');
    }

    public function summary(ImportSession $session): View
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);

        return view('imports.customers.summary', [
            'session' => $session,
            'organization' => $this->tenant->get(),
            'duplicateRows' => (int) ($session->validation_summary['duplicate_rows'] ?? 0),
        ]);
    }

    public function errors(ImportSession $session): StreamedResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);

        return $this->imports->errorReport($session);
    }

    public function validationReport(ImportSession $session): StreamedResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);

        return $this->validationReports->downloadCsv($session->fresh());
    }

    public function validationReportXlsx(ImportSession $session): StreamedResponse
    {
        $this->authorizeSession($session);
        abort_unless($session->entity_type === 'customer', 404);

        return $this->validationReports->downloadXlsx($session->fresh());
    }

    protected function authorizeSession(ImportSession $session): void
    {
        $this->authorize('create', Customer::class);

        $organization = $this->tenant->get();
        abort_unless($organization, 404);
        abort_unless(auth()->user()?->hasPermission('imports.view', $organization), 403);

        $found = $this->imports->findForOrganization($organization, $session->id);
        abort_unless($found, 404);
    }
}
