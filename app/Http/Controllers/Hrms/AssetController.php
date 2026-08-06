<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AssignAssetRequest;
use App\Http\Requests\Hrms\CreateAssetRequest;
use App\Http\Requests\Hrms\ReturnAssetRequest;
use App\Http\Requests\Hrms\UpdateAssetRequest;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Services\Hrms\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(protected AssetService $service)
    {
        $this->authorizeResource(EmployeeAsset::class, 'asset');
    }

    public function index(Request $request): View
    {
        $query = EmployeeAsset::query()->with('employee')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('hrms.assets.index', [
            'assets' => $query->paginate(15)->withQueryString(),
            'employees' => Employee::query()->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))->orderBy('first_name')->get(),
            'statuses' => config('hrms.asset_statuses', []),
            'categories' => config('hrms.asset_categories', []),
        ]);
    }

    public function show(EmployeeAsset $asset): View
    {
        $asset->load(['employee', 'assignments.employee', 'assignments.assignedByUser']);

        return view('hrms.assets.show', [
            'asset' => $asset,
            'employees' => Employee::query()->whereIn('status', config('hrms.leave_applicable_employee_statuses', []))->orderBy('first_name')->get(),
        ]);
    }

    public function store(CreateAssetRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.assets.index')->with('status', 'hrms-asset-created');
    }

    public function update(UpdateAssetRequest $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->service->update($asset, $request->validated(), $request->user());

        return redirect()->route('hrms.assets.show', $asset)->with('status', 'hrms-asset-updated');
    }

    public function destroy(EmployeeAsset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('hrms.assets.index')->with('status', 'hrms-asset-deleted');
    }

    public function assign(AssignAssetRequest $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->service->assign($asset, $request->employee(), $request->validated(), $request->user());

        return redirect()->route('hrms.assets.show', $asset)->with('status', 'hrms-asset-assigned');
    }

    public function returnAsset(ReturnAssetRequest $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->service->returnAsset($asset, $request->validated(), $request->user());

        return redirect()->route('hrms.assets.show', $asset)->with('status', 'hrms-asset-returned');
    }

    public function markLost(Request $request, EmployeeAsset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);
        $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
        $this->service->markLost($asset, $request->only('notes'), $request->user());

        return redirect()->route('hrms.assets.show', $asset)->with('status', 'hrms-asset-lost');
    }
}
