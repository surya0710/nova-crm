<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiCrmEmailTemplateRequest;
use App\Http\Requests\StoreCrmEmailTemplateRequest;
use App\Http\Requests\UpdateCrmEmailTemplateRequest;
use App\Http\Resources\CrmEmailTemplateResource;
use App\Models\CrmEmailTemplate;
use App\Services\CrmEmailTemplateService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrmEmailTemplateApiController extends Controller
{
    public function __construct(
        protected CrmEmailTemplateService $templates,
        protected TenantContext $tenant,
    ) {}

    public function index(IndexApiCrmEmailTemplateRequest $request): AnonymousResourceCollection
    {
        $query = CrmEmailTemplate::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return CrmEmailTemplateResource::collection(
            $query->latest()->paginate($request->perPage())->withQueryString()
        );
    }

    public function show(CrmEmailTemplate $template): CrmEmailTemplateResource
    {
        $this->authorize('view', $template);
        abort_unless((int) $template->organization_id === (int) ($this->tenant->id() ?? 0), 404);

        return new CrmEmailTemplateResource($template);
    }

    public function store(StoreCrmEmailTemplateRequest $request): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        $template = $this->templates->create($organization, $request->validated(), $request->user());

        return (new CrmEmailTemplateResource($template))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCrmEmailTemplateRequest $request, CrmEmailTemplate $template): CrmEmailTemplateResource
    {
        $template = $this->templates->update($template, $request->validated(), $request->user());

        return new CrmEmailTemplateResource($template);
    }

    public function destroy(CrmEmailTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);
        $template->delete();

        return response()->json(['success' => true]);
    }
}
