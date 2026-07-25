<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateShiftRequest;
use App\Http\Requests\Hrms\UpdateShiftRequest;
use App\Models\HrmsShift;
use App\Services\Hrms\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(protected AttendanceService $service)
    {
        $this->authorizeResource(HrmsShift::class, 'shift');
    }

    public function index(): View
    {
        return view('hrms.shifts.index', [
            'shifts' => HrmsShift::query()->latest()->paginate(15),
        ]);
    }

    public function store(CreateShiftRequest $request): RedirectResponse
    {
        $this->service->createShift($request->validated(), $request->user());

        return redirect()->route('hrms.shifts.index')->with('status', 'hrms-shift-created');
    }

    public function update(UpdateShiftRequest $request, HrmsShift $shift): RedirectResponse
    {
        $this->service->updateShift($shift, $request->validated(), $request->user());

        return redirect()->route('hrms.shifts.index')->with('status', 'hrms-shift-updated');
    }

    public function destroy(HrmsShift $shift): RedirectResponse
    {
        $this->authorize('delete', $shift);
        $this->service->deleteShift($shift, request()->user());

        return redirect()->route('hrms.shifts.index')->with('status', 'hrms-shift-deleted');
    }
}
