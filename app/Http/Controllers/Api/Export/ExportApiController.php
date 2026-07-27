<?php

namespace App\Http\Controllers\Api\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\StoreExportRequest;
use App\Models\ExportSession;
use App\Services\Export\ExportCatalogService;
use App\Services\Export\ExportPlatformService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportApiController extends Controller
{
    public function __construct(
        protected ExportPlatformService $exports,
        protected ExportCatalogService $catalog,
        protected TenantContext $tenant,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeView($request);

        return response()->json([
            'groups' => $this->catalog->groupedFor($request->user(), $organization),
            'formats' => config('export.formats', []),
            'module_labels' => config('export.module_labels', []),
        ]);
    }

    public function generate(StoreExportRequest $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        $validated = $request->validated();

        $session = $this->exports->start(
            $organization,
            $request->user(),
            $validated['entity_type'],
            $validated['format'],
            [
                'mode' => $validated['selection_mode'],
                'ids' => $validated['ids'] ?? [],
                'filters' => $validated['filters'] ?? [],
            ],
            $validated['columns'] ?? null,
        );

        return response()->json([
            'message' => __('Export started.'),
            'session' => $session,
        ], 201);
    }

    public function show(Request $request, ExportSession $session): JsonResponse
    {
        $this->authorizeSession($session);

        return response()->json([
            'session' => $session->load('initiator:id,name,email'),
            'progress_percent' => $session->progressPercent(),
            'duration_seconds' => $session->durationSeconds(),
            'downloadable' => $session->isDownloadable(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeView($request);

        $query = ExportSession::query()
            ->where('organization_id', $organization->id)
            ->with('initiator:id,name,email')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json($query->paginate(min(50, (int) $request->integer('per_page', 20))));
    }

    public function download(Request $request, ExportSession $session): StreamedResponse
    {
        $this->authorizeSession($session);

        return $this->exports->download($session, $request->user());
    }

    public function destroy(Request $request, ExportSession $session): JsonResponse
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $session->organization_id === (int) $organization->id, 404);
        abort_unless(
            $request->user()?->hasPermission('exports.manage')
            || $request->user()?->isOwnerOf($organization),
            403
        );

        $this->exports->delete($session, $request->user());

        return response()->json(['message' => __('Export deleted.')]);
    }

    protected function requireOrganization()
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return $organization;
    }

    protected function authorizeView(Request $request): void
    {
        $organization = $this->requireOrganization();
        abort_unless(
            $request->user()?->hasPermission('exports.view')
            || $request->user()?->hasPermission('exports.manage')
            || $request->user()?->hasPermission('exports.create')
            || $request->user()?->isOwnerOf($organization),
            403
        );
    }

    protected function authorizeSession(ExportSession $session): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $session->organization_id === (int) $organization->id, 404);
        $this->authorizeView(request());
    }
}
