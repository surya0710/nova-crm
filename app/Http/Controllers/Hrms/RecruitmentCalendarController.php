<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\SyncRecruitmentCalendarRequest;
use App\Models\InterviewRound;
use App\Models\RecruitmentCalendarEvent;
use App\Models\RecruitmentProvider;
use App\Services\Recruitment\RecruitmentCalendarService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentCalendarController extends Controller
{
    public function __construct(protected RecruitmentCalendarService $calendar)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.view', $organization), 403);

        return view('hrms.recruitment.integrations.calendar', [
            'events' => RecruitmentCalendarEvent::query()
                ->where('organization_id', $organization->id)
                ->with(['provider', 'interviewRound'])
                ->latest()
                ->paginate(20),
            'providers' => RecruitmentProvider::query()
                ->where('organization_id', $organization->id)
                ->where('category', 'calendar')
                ->orderBy('slug')
                ->get(),
            'rounds' => InterviewRound::query()
                ->where('organization_id', $organization->id)
                ->whereNotNull('scheduled_at')
                ->where('status', 'scheduled')
                ->latest('scheduled_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function sync(SyncRecruitmentCalendarRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.integration.manage', $organization), 403);

        $round = InterviewRound::query()->findOrFail($request->integer('interview_round_id'));
        $provider = RecruitmentProvider::query()->findOrFail($request->integer('recruitment_provider_id'));
        abort_unless((int) $round->organization_id === (int) $organization->id, 404);
        abort_unless((int) $provider->organization_id === (int) $organization->id, 404);

        $this->calendar->syncInterviewEvent($round, $provider, $user);

        return back()->with('status', 'recruitment-calendar-synced');
    }
}