<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StoreAttendanceGeofenceRequest;
use App\Http\Requests\Hrms\UpdateAttendanceGeofenceRequest;
use App\Models\AttendanceGeofence;
use App\Models\Branch;
use App\Services\Hrms\GeofenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceGeofenceController extends Controller
{
    public function __construct(protected GeofenceService $service)
    {
        $this->authorizeResource(AttendanceGeofence::class, 'geofence');
    }

    public function index(): View
    {
        return view('hrms.attendance.geofences.index', [
            'geofences' => AttendanceGeofence::query()
                ->with('branch')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(15),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAttendanceGeofenceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->create($data, $request->user());

        return redirect()
            ->route('hrms.attendance.geofences.index')
            ->with('status', 'hrms-geofence-created');
    }

    public function update(UpdateAttendanceGeofenceRequest $request, AttendanceGeofence $geofence): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->service->update($geofence, $data);

        return redirect()
            ->route('hrms.attendance.geofences.index')
            ->with('status', 'hrms-geofence-updated');
    }

    public function destroy(AttendanceGeofence $geofence): RedirectResponse
    {
        $this->service->delete($geofence);

        return redirect()
            ->route('hrms.attendance.geofences.index')
            ->with('status', 'hrms-geofence-deleted');
    }
}
