<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetadataFieldDefinitionRequest;
use App\Http\Requests\UpdateMetadataFieldDefinitionRequest;
use App\Models\MetadataFieldDefinition;
use App\Services\MetadataFieldDefinitionService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataFieldDefinitionController extends Controller
{
    public function __construct(
        protected MetadataFieldDefinitionService $fieldService,
    ) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorize('viewAny', MetadataFieldDefinition::class);

        $query = MetadataFieldDefinition::query()
            ->with(['group', 'options'])
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($entity = $request->string('entity')->toString()) {
            $query->where('entity_type', $entity);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('label', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        return view('metadata-fields.index', [
            'organization' => $tenant->get(),
            'fields' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['entity', 'status', 'search']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MetadataFieldDefinition::class);

        return view('metadata-fields.create', [
            'field' => new MetadataFieldDefinition([
                'status' => 'draft',
                'type' => 'text',
                'source' => 'manual',
                'is_exportable' => true,
                'is_api_visible' => true,
            ]),
            'optionsText' => '',
        ]);
    }

    public function store(StoreMetadataFieldDefinitionRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $field = $this->fieldService->create($organization, $request->validated(), $request->user());

        return redirect()
            ->route('metadata-fields.show', $field)
            ->with('status', 'metadata-field-created');
    }

    public function show(MetadataFieldDefinition $metadata_field): View
    {
        $this->authorize('view', $metadata_field);

        $metadata_field->load(['group', 'options', 'versions.creator']);

        return view('metadata-fields.show', [
            'field' => $metadata_field,
        ]);
    }

    public function edit(MetadataFieldDefinition $metadata_field): View
    {
        $this->authorize('update', $metadata_field);

        $metadata_field->load(['group', 'options']);

        return view('metadata-fields.edit', [
            'field' => $metadata_field,
            'optionsText' => $metadata_field->options
                ->map(fn ($option) => $option->value.'|'.$option->label)
                ->implode(PHP_EOL),
        ]);
    }

    public function update(UpdateMetadataFieldDefinitionRequest $request, MetadataFieldDefinition $metadata_field): RedirectResponse
    {
        $field = $this->fieldService->update($metadata_field, $request->validated(), $request->user());

        return redirect()
            ->route('metadata-fields.show', $field)
            ->with('status', 'metadata-field-updated');
    }

    public function destroy(MetadataFieldDefinition $metadata_field): RedirectResponse
    {
        $this->authorize('delete', $metadata_field);

        $this->fieldService->archive($metadata_field, request()->user());

        return redirect()
            ->route('metadata-fields.index')
            ->with('status', 'metadata-field-archived');
    }

    public function publish(MetadataFieldDefinition $metadata_field): RedirectResponse
    {
        $this->authorize('update', $metadata_field);

        $this->fieldService->publish($metadata_field, request()->user());

        return redirect()
            ->route('metadata-fields.show', $metadata_field)
            ->with('status', 'metadata-field-published');
    }

    public function activate(MetadataFieldDefinition $metadata_field): RedirectResponse
    {
        $this->authorize('update', $metadata_field);

        $this->fieldService->activate($metadata_field, request()->user());

        return redirect()
            ->route('metadata-fields.show', $metadata_field)
            ->with('status', 'metadata-field-activated');
    }

    public function deactivate(MetadataFieldDefinition $metadata_field): RedirectResponse
    {
        $this->authorize('update', $metadata_field);

        $this->fieldService->deactivate($metadata_field, request()->user());

        return redirect()
            ->route('metadata-fields.show', $metadata_field)
            ->with('status', 'metadata-field-deactivated');
    }
}
