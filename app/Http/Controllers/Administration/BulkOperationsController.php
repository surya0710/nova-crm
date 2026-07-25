<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\BulkOperation;
use App\Services\Bulk\BulkActionRegistry;
use App\Services\Bulk\BulkOperationsService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkOperationsController extends Controller
{
    public function __construct(
        protected BulkOperationsService $bulk,
        protected BulkActionRegistry $registry,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $organization = $this->requireOrganization();
        $this->authorizeView();

        return view('administration.bulk.index', [
            'groups' => $this->registry->catalogGrouped(),
            'moduleLabels' => config('bulk.module_labels', []),
            'recent' => BulkOperation::query()
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

        $query = BulkOperation::query()
            ->where('organization_id', $organization->id)
            ->with('initiator')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('administration.bulk.history', [
            'operations' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'redirect_to' => ['nullable', 'string'],
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Bulk action started.'),
                'operation' => $operation,
                'redirect' => route('administration.bulk.show', $operation),
            ], 201);
        }

        $redirect = $validated['redirect_to'] ?? route('administration.bulk.show', $operation);

        return redirect()
            ->to($redirect)
            ->with('status', __('Bulk action started (:total records).', ['total' => $operation->total_count]));
    }

    public function show(BulkOperation $operation): View
    {
        $this->authorizeOperation($operation, manage: false);

        return view('administration.bulk.show', [
            'operation' => $operation->load('initiator'),
            'actionLabel' => $this->registry->has($operation->action_key)
                ? $this->registry->resolve($operation->action_key)->label()
                : $operation->action_key,
        ]);
    }

    public function errors(BulkOperation $operation): StreamedResponse
    {
        $this->authorizeOperation($operation, manage: false);

        return $this->bulk->errorReport($operation);
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
            $user?->hasPermission('bulk.view')
            || $user?->hasPermission('bulk.manage')
            || $user?->isOwnerOf($this->requireOrganization()),
            403
        );
    }

    protected function authorizeOperation(BulkOperation $operation, bool $manage = true): void
    {
        $organization = $this->requireOrganization();
        abort_unless((int) $operation->organization_id === (int) $organization->id, 404);

        if ($manage) {
            abort_unless(
                request()->user()?->hasPermission('bulk.manage')
                || request()->user()?->isOwnerOf($organization),
                403
            );
        } else {
            $this->authorizeView();
        }
    }
}
