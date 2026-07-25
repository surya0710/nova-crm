<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\UpdateEmployeeDocumentRequest;
use App\Http\Requests\Hrms\UploadEmployeeDocumentRequest;
use App\Http\Requests\Hrms\VerifyEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\Hrms\EmployeeDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function __construct(protected EmployeeDocumentService $service) {}

    public function index(Employee $employee): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);
        $this->authorize('view', $employee);

        return view('hrms.employees.documents.index', [
            'employee' => $employee,
            'documents' => $employee->documents()
                ->with(['currentVersion.uploader', 'verifier'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Employee $employee): View
    {
        $this->authorize('manage', EmployeeDocument::class);
        $this->authorize('view', $employee);

        return view('hrms.employees.documents.create', [
            'employee' => $employee,
        ]);
    }

    public function store(UploadEmployeeDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('view', $employee);

        $document = $this->service->uploadDocument(
            $employee,
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        return redirect()
            ->route('hrms.employees.documents.show', [$employee, $document])
            ->with('status', 'hrms-document-uploaded');
    }

    public function show(Employee $employee, EmployeeDocument $document): View
    {
        $this->authorize('view', $document);
        abort_unless($document->employee_id === $employee->id, 404);

        $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);

        return view('hrms.employees.documents.show', [
            'employee' => $employee,
            'document' => $document,
        ]);
    }

    public function update(UpdateEmployeeDocumentRequest $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        $this->service->updateDocument(
            $document,
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        return redirect()
            ->route('hrms.employees.documents.show', [$employee, $document])
            ->with('status', 'hrms-document-updated');
    }

    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('manage', $document);
        abort_unless($document->employee_id === $employee->id, 404);

        $this->service->deleteDocument($document, request()->user());

        return redirect()
            ->route('hrms.employees.documents.index', $employee)
            ->with('status', 'hrms-document-deleted');
    }

    public function download(Request $request, Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($document->employee_id === $employee->id, 404);

        $version = $this->service->resolveDownloadVersion(
            $document,
            $request->integer('version') ?: null,
        );

        abort_unless(Storage::disk($version->disk)->exists($version->path), 404);

        $this->service->logDownload($document, $version, $request->user());

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }

    public function verify(VerifyEmployeeDocumentRequest $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        $this->service->verifyDocument($document, $request->validated(), $request->user());

        return redirect()
            ->route('hrms.employees.documents.show', [$employee, $document])
            ->with('status', 'hrms-document-verified');
    }

    public function restoreVersion(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('manage', $document);
        abort_unless($document->employee_id === $employee->id, 404);

        $request->validate(['version_id' => ['required', 'integer', 'min:1']]);

        $this->service->restoreVersion($document, (int) $request->input('version_id'), $request->user());

        return redirect()
            ->route('hrms.employees.documents.show', [$employee, $document])
            ->with('status', 'hrms-document-version-restored');
    }
}
