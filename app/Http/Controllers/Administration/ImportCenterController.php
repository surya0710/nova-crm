<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\ImportSession;
use App\Services\Import\Adapters\GenericImportTemplateAdapter;
use App\Services\Import\ImportCatalogService;
use App\Services\Import\ImportEntityRegistry;
use App\Services\Import\ImportPlatformService;
use App\Services\Import\ImportTemplateService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportCenterController extends Controller
{
    public function __construct(
        protected ImportPlatformService $imports,
        protected ImportCatalogService $catalog,
        protected ImportEntityRegistry $registry,
        protected ImportTemplateService $templates,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeView();

        return view('administration.imports.index', [
            'groups' => $this->catalog->groupedFor(request()->user(), $organization),
            'moduleLabels' => config('import.module_labels', []),
        ]);
    }

    public function history(Request $request): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeView();

        $query = ImportSession::query()
            ->where('organization_id', $organization->id)
            ->with('uploader')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where('original_filename', 'like', $q);
        }

        return view('administration.imports.history', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'entityTypes' => $this->registry->types(),
        ]);
    }

    public function create(string $entity): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeEntity($entity);

        $adapter = $this->registry->resolve($entity);

        return view('administration.imports.create', [
            'entityType' => $entity,
            'entityLabel' => $adapter->entityLabel(),
            'fields' => $adapter->fieldDefinitions(),
        ]);
    }

    public function downloadTemplate(string $entity, string $format): StreamedResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeEntity($entity);

        $provider = new GenericImportTemplateAdapter($this->registry->resolve($entity));

        return $format === 'xlsx'
            ? $this->templates->downloadXlsx($provider, $organization)
            : $this->templates->downloadCsv($provider, $organization);
    }

    public function store(Request $request, string $entity): RedirectResponse
    {
        $organization = $this->requireOrganization();
        $this->authorizeEntity($entity);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.config('import.max_upload_kilobytes', 10240),
                'mimes:csv,txt,xlsx',
            ],
            'duplicate_strategy' => ['nullable', 'in:skip,update,create'],
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

        $session = $this->imports->validate($session->fresh(), $request->user());

        return redirect()
            ->route('administration.imports.preview', $session)
            ->with('status', 'import-uploaded');
    }

    public function preview(ImportSession $session): View
    {
        $this->authorizeSession($session, manage: false);
        $preview = $this->imports->preview($session, request()->user());
        $entity = $this->registry->resolve($session->entity_type);

        return view('administration.imports.preview', [
            'session' => $session->fresh(),
            'preview' => $preview,
            'fields' => $entity->fieldDefinitions(),
            'entityLabel' => $entity->entityLabel(),
        ]);
    }

    public function map(Request $request, ImportSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string'],
        ]);

        $this->imports->applyMapping($session, $validated['mapping'], $request->user());

        return redirect()
            ->route('administration.imports.preview', $session)
            ->with('status', 'import-mapped');
    }

    public function execute(Request $request, ImportSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        $validated = $request->validate([
            'duplicate_strategy' => ['nullable', 'in:skip,update,create'],
            'confirm' => ['accepted'],
        ]);

        $session = $this->imports->startImport($session, $request->user(), [
            'duplicate_strategy' => $validated['duplicate_strategy'] ?? null,
        ]);

        return redirect()
            ->route('administration.imports.show', $session)
            ->with('status', 'import-started');
    }

    public function show(ImportSession $session): View
    {
        $this->authorizeSession($session, manage: false);

        return view('administration.imports.show', [
            'session' => $session->load('uploader'),
            'entityLabel' => $this->registry->has($session->entity_type)
                ? $this->registry->resolve($session->entity_type)->entityLabel()
                : $session->entity_type,
        ]);
    }

    public function errors(ImportSession $session): StreamedResponse
    {
        $this->authorizeSession($session, manage: false);

        return $this->imports->errorReport($session);
    }

    protected function requireOrganization()
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        return $organization;
    }

    protected function authorizeView(): void
    {
        $user = request()->user();
        abort_unless(
            $user?->hasPermission('imports.view')
            || $user?->hasPermission('imports.create')
            || $user?->hasPermission('imports.manage'),
            403
        );
    }

    protected function authorizeEntity(string $entity, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless($this->catalog->userCanAccessEntity(request()->user(), $organization, $entity), 403);

        if ($manage) {
            abort_unless(
                request()->user()?->hasPermission('imports.create')
                || request()->user()?->hasPermission('imports.manage'),
                403
            );
        } else {
            $this->authorizeView();
        }
    }

    protected function authorizeSession(ImportSession $session, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $session->organization_id === (int) $organization->id, 404);
        $this->authorizeEntity($session->entity_type, $manage);
    }
}
