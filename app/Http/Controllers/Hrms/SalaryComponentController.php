<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateSalaryComponentRequest;
use App\Http\Requests\Hrms\UpdateSalaryComponentRequest;
use App\Models\SalaryComponent;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    public function __construct(protected PayrollService $service)
    {
        $this->authorizeResource(SalaryComponent::class, 'component');
    }

    public function index(): View
    {
        return view('hrms.payroll.components.index', [
            'components' => SalaryComponent::query()->latest()->paginate(20),
            'componentTypes' => config('hrms.salary_component_types', []),
        ]);
    }

    public function store(CreateSalaryComponentRequest $request): RedirectResponse
    {
        $this->service->createSalaryComponent($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.components.index')
            ->with('status', 'hrms-salary-component-created');
    }

    public function update(UpdateSalaryComponentRequest $request, SalaryComponent $component): RedirectResponse
    {
        $this->service->updateSalaryComponent($component, $request->validated(), $request->user());

        return redirect()->route('hrms.payroll.components.index')
            ->with('status', 'hrms-salary-component-updated');
    }

    public function destroy(SalaryComponent $component): RedirectResponse
    {
        $this->service->deleteSalaryComponent($component, request()->user());

        return redirect()->route('hrms.payroll.components.index')
            ->with('status', 'hrms-salary-component-deleted');
    }
}
