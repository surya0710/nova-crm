<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateDepartmentRequest;
use App\Http\Requests\Hrms\UpdateDepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Services\Hrms\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $service)
    {
        $this->authorizeResource(Department::class, 'department');
    }

    public function index(): View
    {
        return view('hrms.departments.index', [
            'departments' => Department::query()->with(['parent', 'branch'])->latest()->paginate(15),
            'branches' => Branch::query()->orderBy('name')->get(),
            'parents' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CreateDepartmentRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.departments.index')->with('status', 'hrms-department-created');
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->service->update($department, $request->validated(), $request->user());

        return redirect()->route('hrms.departments.index')->with('status', 'hrms-department-updated');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);
        $department->delete();

        return redirect()->route('hrms.departments.index')->with('status', 'hrms-department-deleted');
    }
}
