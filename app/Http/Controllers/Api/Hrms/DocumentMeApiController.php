<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\Mobile\UploadMyDocumentRequest;
use App\Http\Resources\Hrms\DocumentResource;
use App\Models\EmployeeDocument;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Services\Hrms\MobileUploadValidator;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
        protected MobileUploadValidator $uploadValidator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', EmployeeDocument::class);

        $query = EmployeeDocument::query()
            ->where('employee_id', $employee->id)
            ->with(['currentVersion']);
        ApiQuery::applyFilters($query, $request, [
            'category' => 'category',
            'verification_status' => 'verification_status',
        ]);

        $paginator = $query->latest()->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn (EmployeeDocument $doc) => (new DocumentResource($doc))->resolve(),
        );
    }

    public function store(UploadMyDocumentRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $file = $request->file('file');
        $this->uploadValidator->validate($file, 'document');

        $document = $this->facade->documents()->uploadDocument(
            $employee,
            $request->validated(),
            $file,
            $request->user(),
        );

        return ApiResponse::success(
            new DocumentResource($document),
            __('Document uploaded.'),
            status: 201,
        );
    }

    public function show(Request $request, EmployeeDocument $document): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'versions']);

        return ApiResponse::success(new DocumentResource($document));
    }

    public function download(Request $request, EmployeeDocument $document): StreamedResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);
        $this->authorize('view', $document);

        $docs = $this->facade->documents();
        $version = $docs->resolveDownloadVersion(
            $document,
            $request->integer('version_id') ?: null,
        );
        $docs->logDownload($document, $version, $request->user());

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }
}
