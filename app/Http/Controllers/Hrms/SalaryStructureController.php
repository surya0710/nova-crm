<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateSalaryStructureRequest;
use App\Http\Requests\Hrms\UpdateSalaryStructureRequest;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Services\Hrms\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalaryStructureController extends Controller
{
    public function __construct(protected PayrollService $service)
    {
        $this->authorizeResource(SalaryStructure::class, 'structure');
    }

    public function index(): View
    {
        return view('hrms.payroll.structures.index', [
            'structures' => SalaryStructure::query()
                ->withCount('structureComponents')
                ->latest()
                ->paginate(20),
            'availableComponents' => SalaryComponent::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'calculationTypes' => config('hrms.salary_calculation_types', []),
        ]);
    }

    public function store(CreateSalaryStructureRequest $request): RedirectResponse
    {
        $this->service->createSalaryStructure($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.structures.index')
            ->with('status', 'hrms-salary-structure-created');
    }

    public function update(UpdateSalaryStructureRequest $request, SalaryStructure $structure): RedirectResponse
    {
        $this->service->updateSalaryStructure($structure, $request->validated(), $request->user());

        return redirect()->route('hrms.payroll.structures.index')
            ->with('status', 'hrms-salary-structure-updated');
    }

    public function destroy(SalaryStructure $structure): RedirectResponse
    {
        $this->service->deleteSalaryStructure($structure, request()->user());

        return redirect()->route('hrms.payroll.structures.index')
            ->with('status', 'hrms-salary-structure-deleted');
    }
}
