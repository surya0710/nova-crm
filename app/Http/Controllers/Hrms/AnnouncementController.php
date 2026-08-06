<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateAnnouncementRequest;
use App\Http\Requests\Hrms\UpdateAnnouncementRequest;
use App\Models\HrmsAnnouncement;
use App\Services\Hrms\HrmsDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(protected HrmsDashboardService $service)
    {
        $this->authorizeResource(HrmsAnnouncement::class, 'announcement');
    }

    public function index(): View
    {
        return view('hrms.announcements.index', [
            'announcements' => HrmsAnnouncement::query()->latest()->paginate(15),
            'audiences' => config('hrms.announcement_audiences', []),
        ]);
    }

    public function store(CreateAnnouncementRequest $request): RedirectResponse
    {
        $this->service->createAnnouncement($request->validated(), $request->user());

        return redirect()->route('hrms.announcements.index')->with('status', 'hrms-announcement-created');
    }

    public function update(UpdateAnnouncementRequest $request, HrmsAnnouncement $announcement): RedirectResponse
    {
        $this->service->updateAnnouncement($announcement, $request->validated(), $request->user());

        return redirect()->route('hrms.announcements.index')->with('status', 'hrms-announcement-updated');
    }

    public function destroy(HrmsAnnouncement $announcement): RedirectResponse
    {
        $this->service->deleteAnnouncement($announcement, request()->user());

        return redirect()->route('hrms.announcements.index')->with('status', 'hrms-announcement-deleted');
    }
}
