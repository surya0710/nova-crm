<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateDesignationRequest;
use App\Http\Requests\Hrms\UpdateDesignationRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Services\Hrms\DesignationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(protected DesignationService $service)
    {
        $this->authorizeResource(Designation::class, 'designation');
    }

    public function index(): View
    {
        return view('hrms.designations.index', [
            'designations' => Designation::query()->with('department')->latest()->paginate(15),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CreateDesignationRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.designations.index')->with('status', 'hrms-designation-created');
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $this->service->update($designation, $request->validated(), $request->user());

        return redirect()->route('hrms.designations.index')->with('status', 'hrms-designation-updated');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $this->authorize('delete', $designation);
        $designation->delete();

        return redirect()->route('hrms.designations.index')->with('status', 'hrms-designation-deleted');
    }
}
