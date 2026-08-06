<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\StoreExportRequest;
use App\Models\ExportSession;
use App\Services\Export\ExportCatalogService;
use App\Services\Export\ExportDefinitionRegistry;
use App\Services\Export\ExportPlatformService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCenterController extends Controller
{
    public function __construct(
        protected ExportPlatformService $exports,
        protected ExportCatalogService $catalog,
        protected ExportDefinitionRegistry $registry,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeView();

        return view('administration.exports.index', [
            'groups' => $this->catalog->groupedFor(request()->user(), $organization),
            'moduleLabels' => config('export.module_labels', []),
            'formats' => config('export.formats', []),
            'recent' => ExportSession::query()
                ->where('organization_id', $organization->id)
                ->with('initiator')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function history(Request $request): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeView();

        $query = ExportSession::query()
            ->where('organization_id', $organization->id)
            ->with('initiator')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('format')) {
            $query->where('format', $request->string('format')->toString());
        }
        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('entity_type', 'like', $q)
                    ->orWhere('original_filename', 'like', $q);
            });
        }

        return view('administration.exports.history', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'formats' => config('export.formats', []),
        ]);
    }

    public function create(string $entity): View
    {
        $organization = $this->requireOrganization();
        abort_unless($this->catalog->userCanAccessEntity(request()->user(), $organization, $entity), 403);
        abort_unless($this->registry->has($entity), 404);

        $adapter = $this->registry->resolve($entity);

        return view('administration.exports.create', [
            'entity' => $entity,
            'label' => config('export.entities.'.$entity.'.label', $adapter->entityLabel()),
            'columns' => $this->exports->columnsFor($entity),
            'defaultColumns' => $adapter->defaultColumns(),
            'formats' => config('export.formats', []),
        ]);
    }

    public function store(StoreExportRequest $request): RedirectResponse|JsonResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Export started.'),
                'session' => $session,
                'redirect' => route('administration.exports.show', $session),
            ], 201);
        }

        $redirect = $validated['redirect_to'] ?? route('administration.exports.show', $session);

        return redirect()
            ->to($redirect)
            ->with('status', __('Export started (:total records).', ['total' => $session->total_count]));
    }

    public function show(ExportSession $session): View
    {
        $this->authorizeSession($session, manage: false);

        return view('administration.exports.show', [
            'session' => $session->load('initiator'),
            'entityLabel' => config('export.entities.'.$session->entity_type.'.label', $session->entity_type),
            'formatLabel' => config('export.formats.'.$session->format.'.label', strtoupper($session->format)),
        ]);
    }

    public function download(ExportSession $session): StreamedResponse
    {
        $this->authorizeSession($session, manage: false);

        return $this->exports->download($session, request()->user());
    }

    public function revoke(ExportSession $session): RedirectResponse
    {
        $this->authorizeSession($session, manage: true);
        $this->exports->revoke($session, request()->user());

        return redirect()
            ->route('administration.exports.show', $session)
            ->with('status', __('Download link revoked.'));
    }

    public function regenerate(ExportSession $session): RedirectResponse
    {
        $this->authorizeSession($session, manage: true);
        $fresh = $this->exports->regenerate($session, request()->user());

        return redirect()
            ->route('administration.exports.show', $fresh)
            ->with('status', __('Export regeneration started.'));
    }

    public function destroy(ExportSession $session): RedirectResponse
    {
        $this->authorizeSession($session, manage: true);
        $this->exports->delete($session, request()->user());

        return redirect()
            ->route('administration.exports.history')
            ->with('status', __('Export deleted.'));
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
            $user?->hasPermission('exports.view')
            || $user?->hasPermission('exports.manage')
            || $user?->hasPermission('exports.create')
            || $user?->isOwnerOf($this->requireOrganization()),
            403
        );
    }

    protected function authorizeSession(ExportSession $session, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $session->organization_id === (int) $organization->id, 404);

        if ($manage) {
            abort_unless(
                request()->user()?->hasPermission('exports.manage')
                || request()->user()?->isOwnerOf($organization),
                403
            );
        } else {
            $this->authorizeView();
        }
    }
}
