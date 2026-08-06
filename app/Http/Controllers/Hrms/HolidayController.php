<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateHolidayRequest;
use App\Http\Requests\Hrms\UpdateHolidayRequest;
use App\Models\Branch;
use App\Models\Holiday;
use App\Services\Hrms\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function __construct(protected LeaveService $service)
    {
        $this->authorizeResource(Holiday::class, 'holiday');
    }

    public function index(): View
    {
        return view('hrms.holidays.index', [
            'holidays' => Holiday::query()->with('branch')->orderBy('holiday_date')->paginate(15),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(CreateHolidayRequest $request): RedirectResponse
    {
        $this->service->createHoliday($request->validated(), $request->user());

        return redirect()->route('hrms.holidays.index')->with('status', 'hrms-holiday-created');
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $this->service->updateHoliday($holiday, $request->validated(), $request->user());

        return redirect()->route('hrms.holidays.index')->with('status', 'hrms-holiday-updated');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->service->deleteHoliday($holiday, request()->user());

        return redirect()->route('hrms.holidays.index')->with('status', 'hrms-holiday-deleted');
    }
}
