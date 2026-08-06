<?php

namespace App\Http\Controllers\Ess;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDocument;
use App\Services\Hrms\EmployeeDocumentService;
use App\Services\Hrms\EssContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EssDocumentController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected EmployeeDocumentService $service,
    ) {}

    public function index(): View
    {
        $employee = $this->essContext->requireEmployee();
        $this->authorize('viewAny', EmployeeDocument::class);

        return view('ess.documents.index', [
            'employee' => $employee,
            'documents' => $employee->documents()
                ->with(['currentVersion', 'versions'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(EmployeeDocument $document): View
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless($document->employee_id === $employee->id, 404);
        $this->authorize('view', $document);

        $document->load(['currentVersion.uploader', 'versions.uploader', 'verifier']);

        return view('ess.documents.show', [
            'employee' => $employee,
            'document' => $document,
        ]);
    }

    public function download(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $employee = $this->essContext->requireEmployee();
        abort_unless($document->employee_id === $employee->id, 404);
        $this->authorize('view', $document);

        $version = $this->service->resolveDownloadVersion($document, $request->integer('version_id') ?: null);
        $this->service->logDownload($document, $version, $request->user());

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }
}
