<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateTeamRequest;
use App\Http\Requests\Hrms\UpdateTeamRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HrmsTeam;
use App\Services\Hrms\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(protected TeamService $service)
    {
        $this->authorizeResource(HrmsTeam::class, 'team');
    }

    public function index(): View
    {
        return view('hrms.teams.index', [
            'teams' => HrmsTeam::query()->with(['department', 'teamLead'])->latest()->paginate(15),
            'departments' => Department::query()->orderBy('name')->get(),
            'employees' => Employee::query()->orderBy('first_name')->get(),
        ]);
    }

    public function store(CreateTeamRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('hrms.teams.index')->with('status', 'hrms-team-created');
    }

    public function update(UpdateTeamRequest $request, HrmsTeam $team): RedirectResponse
    {
        $this->service->update($team, $request->validated(), $request->user());

        return redirect()->route('hrms.teams.index')->with('status', 'hrms-team-updated');
    }

    public function destroy(HrmsTeam $team): RedirectResponse
    {
        $this->authorize('delete', $team);
        $team->delete();

        return redirect()->route('hrms.teams.index')->with('status', 'hrms-team-deleted');
    }
}
