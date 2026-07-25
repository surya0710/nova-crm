<?php

namespace App\Http\Controllers\Api\Bulk;

use App\Http\Controllers\Controller;
use App\Models\BulkOperation;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkApiController extends Controller
{
    public function __construct(
        protected BulkOperationsService $bulk,
        protected TenantContext $tenant,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();

        $validated = $request->validate([
            'action_key' => ['required', 'string'],
            'selection_mode' => ['required', 'in:ids,page,all,filtered'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
            'filters' => ['nullable', 'array'],
            'input' => ['nullable', 'array'],
            'confirm' => ['accepted'],
        ]);

        $operation = $this->bulk->start(
            $organization,
            $request->user(),
            $validated['action_key'],
            [
                'mode' => $validated['selection_mode'],
                'ids' => $validated['ids'] ?? [],
                'filters' => $validated['filters'] ?? [],
            ],
            $validated['input'] ?? [],
            true,
        );

        return response()->json([
            'message' => __('Bulk action started.'),
            'operation' => $operation,
        ], 201);
    }

    public function show(Request $request, BulkOperation $operation): JsonResponse
    {
        $this->authorizeOperation($operation);

        return response()->json([
            'operation' => $operation->load('initiator:id,name,email'),
            'progress_percent' => $operation->progressPercent(),
            'duration_seconds' => $operation->durationSeconds(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        abort_unless(
            $request->user()?->hasPermission('bulk.view')
            || $request->user()?->hasPermission('bulk.manage')
            || $request->user()?->isOwnerOf($organization),
            403
        );

        $query = BulkOperation::query()
            ->where('organization_id', $organization->id)
            ->with('initiator:id,name,email')
            ->latest();

        return response()->json($query->paginate(min(50, (int) $request->integer('per_page', 20))));
    }

    public function errors(Request $request, BulkOperation $operation): StreamedResponse
    {
        $this->authorizeOperation($operation);

        return $this->bulk->errorReport($operation);
    }

    public function actions(Request $request, string $entity): JsonResponse
    {
        $organization = $this->requireOrganization();

        return response()->json([
            'entity' => $entity,
            'actions' => $this->bulk->availableActionsFor($request->user(), $organization, $entity),
        ]);
    }

    protected function requireOrganization()
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return $organization;
    }

    protected function authorizeOperation(BulkOperation $operation): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $operation->organization_id === (int) $organization->id, 404);
        abort_unless(
            request()->user()?->hasPermission('bulk.view')
            || request()->user()?->hasPermission('bulk.manage')
            || request()->user()?->isOwnerOf($organization),
            403
        );
    }
}
