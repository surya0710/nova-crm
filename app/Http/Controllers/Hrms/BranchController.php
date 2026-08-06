<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateBranchRequest;
use App\Http\Requests\Hrms\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\Hrms\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(protected BranchService $service)
    {
        $this->authorizeResource(Branch::class, 'branch');
    }

    public function index(): View
    {
        return view('hrms.branches.index', [
            'branches' => Branch::query()->latest()->paginate(15),
        ]);
    }

    public function store(CreateBranchRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.branches.index')->with('status', 'hrms-branch-created');
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->service->update($branch, $request->validated(), $request->user());

        return redirect()->route('hrms.branches.index')->with('status', 'hrms-branch-updated');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);
        $branch->delete();

        return redirect()->route('hrms.branches.index')->with('status', 'hrms-branch-deleted');
    }
}
