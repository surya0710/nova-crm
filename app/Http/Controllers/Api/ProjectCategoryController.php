<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectCategoryRequest;
use App\Http\Requests\UpdateProjectCategoryRequest;
use App\Http\Resources\ProjectCategoryResource;
use App\Models\ProjectCategory;
use App\Services\ProjectCategoryService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProjectCategoryController extends Controller
{
    public function __construct(protected ProjectCategoryService $categoryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProjectCategory::class);

        return ProjectCategoryResource::collection(
            ProjectCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 50))
        );
    }

    public function show(ProjectCategory $category): ProjectCategoryResource
    {
        $this->authorize('view', $category);

        return new ProjectCategoryResource($category);
    }

    public function store(StoreProjectCategoryRequest $request, TenantContext $tenant): JsonResponse
    {
        $category = $this->categoryService->create(
            $tenant->get(),
            $request->validated(),
        );

        return (new ProjectCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectCategoryRequest $request, ProjectCategory $category): ProjectCategoryResource
    {
        $category = $this->categoryService->update($category, $request->validated());

        return new ProjectCategoryResource($category);
    }

    public function destroy(ProjectCategory $category): JsonResponse
    {
        $this->authorize('delete', $category);

        try {
            $this->categoryService->delete($category);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
