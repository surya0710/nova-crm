<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectCategoryRequest;
use App\Http\Requests\UpdateProjectCategoryRequest;
use App\Models\ProjectCategory;
use App\Services\ProjectCategoryService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectCategoryController extends Controller
{
    public function __construct(protected ProjectCategoryService $categoryService)
    {
        $this->authorizeResource(ProjectCategory::class, 'category');
    }

    public function index(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        return view('projects.categories.index', [
            'categories' => ProjectCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $organization,
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('projects.categories.create', [
            'category' => new ProjectCategory(['is_active' => true]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProjectCategoryRequest $request, TenantContext $tenant): RedirectResponse
    {
        $category = $this->categoryService->create(
            $tenant->get(),
            $request->validated(),
        );

        return redirect()
            ->route('project-categories.index')
            ->with('status', 'project-category-created');
    }

    public function edit(ProjectCategory $category): View
    {
        return view('projects.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateProjectCategoryRequest $request, ProjectCategory $category): RedirectResponse
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()
            ->route('project-categories.index')
            ->with('status', 'project-category-updated');
    }

    public function destroy(ProjectCategory $category): RedirectResponse
    {
        $this->categoryService->delete($category);

        return redirect()
            ->route('project-categories.index')
            ->with('status', 'project-category-deleted');
    }
}
