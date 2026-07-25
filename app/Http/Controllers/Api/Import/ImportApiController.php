<?php

namespace App\Http\Controllers\Api\Import;

use App\Http\Controllers\Controller;
use App\Models\ImportSession;
use App\Services\Import\ImportCatalogService;
use App\Services\Import\ImportPlatformService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportApiController extends Controller
{
    public function __construct(
        protected ImportPlatformService $imports,
        protected ImportCatalogService $catalog,
        protected TenantContext $tenant,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeView($request);

        return response()->json([
            'modules' => config('import.module_labels', []),
            'groups' => $this->catalog->groupedFor($request->user(), $organization),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeView($request);

        $query = ImportSession::query()
            ->where('organization_id', $organization->id)
            ->with('uploader:id,name,email')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json($query->paginate(min(50, (int) $request->integer('per_page', 20))));
    }

    public function upload(Request $request, string $entity): JsonResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeEntity($request, $entity);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('import.max_upload_kilobytes', 10240),
                'mimes:csv,txt,xlsx',
            ],
            'duplicate_strategy' => ['nullable', 'in:skip,update,create'],
            'validate' => ['nullable', 'boolean'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $session = $this->imports->upload($organization, $entity, $file, $request->user());

        if (! empty($validated['duplicate_strategy'])) {
            $session->forceFill([
                'metadata' => array_merge($session->metadata ?? [], [
                    'duplicate_strategy' => $validated['duplicate_strategy'],
                ]),
            ])->save();
        }

        if ($request->boolean('validate', true)) {
            $session = $this->imports->validate($session->fresh(), $request->user());
        }

        return response()->json([
            'message' => __('Import uploaded.'),
            'session' => $session->fresh(),
        ], 201);
    }

    public function validateSession(Request $request, ImportSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);
        $session = $this->imports->validate($session, $request->user());

        return response()->json([
            'message' => __('Import validated.'),
            'session' => $session,
        ]);
    }

    public function preview(Request $request, ImportSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session, manage: false);
        $preview = $this->imports->preview($session, $request->user());

        return response()->json([
            'session' => $session->fresh(),
            'preview' => $preview->toArray(),
        ]);
    }

    public function map(Request $request, ImportSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string'],
        ]);

        $session = $this->imports->applyMapping($session, $validated['mapping'], $request->user());

        return response()->json([
            'message' => __('Mapping applied.'),
            'session' => $session,
        ]);
    }

    public function execute(Request $request, ImportSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'duplicate_strategy' => ['nullable', 'in:skip,update,create'],
            'confirm' => ['accepted'],
        ]);

        $session = $this->imports->startImport($session, $request->user(), [
            'duplicate_strategy' => $validated['duplicate_strategy'] ?? null,
        ]);

        return response()->json([
            'message' => __('Import started.'),
            'session' => $session,
        ]);
    }

    public function show(Request $request, ImportSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session, manage: false);

        return response()->json([
            'session' => $session->load('uploader:id,name,email'),
        ]);
    }

    public function errors(Request $request, ImportSession $session): StreamedResponse
    {
        $this->authorizeSession($request, $session, manage: false);

        return $this->imports->errorReport($session);
    }

    protected function requireOrganization()
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return $organization;
    }

    protected function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('imports.view')
            || $user?->hasPermission('imports.create')
            || $user?->hasPermission('imports.manage'),
            403
        );
    }

    protected function authorizeEntity(Request $request, string $entity, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless($this->catalog->userCanAccessEntity($request->user(), $organization, $entity), 403);

        if ($manage) {
            abort_unless(
                $request->user()?->hasPermission('imports.create')
                || $request->user()?->hasPermission('imports.manage'),
                403
            );
        } else {
            $this->authorizeView($request);
        }
    }

    protected function authorizeSession(Request $request, ImportSession $session, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $session->organization_id === (int) $organization->id, 404);
        $this->authorizeEntity($request, $session->entity_type, $manage);
    }
}
