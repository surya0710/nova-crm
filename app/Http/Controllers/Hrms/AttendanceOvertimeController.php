<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\ApproveAttendanceOvertimeEntryRequest;
use App\Http\Requests\Hrms\RejectAttendanceOvertimeEntryRequest;
use App\Http\Requests\Hrms\StoreAttendanceOvertimeRuleRequest;
use App\Http\Requests\Hrms\UpdateAttendanceOvertimeRuleRequest;
use App\Models\AttendanceOvertimeEntry;
use App\Models\AttendanceOvertimeRule;
use App\Services\Hrms\AttendanceOvertimeListingService;
use App\Services\Hrms\OvertimeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceOvertimeController extends Controller
{
    public function __construct(
        protected OvertimeCalculationService $overtimeService,
        protected AttendanceOvertimeListingService $listingService,
    ) {}

    public function rulesIndex(Request $request): View
    {
        $this->authorize('manageRules', AttendanceOvertimeRule::class);

        return view('hrms.attendance.overtime.rules', [
            'rules' => $this->listingService->paginateRules($request),
            'ruleTypes' => config('hrms.attendance_overtime_rule_types', []),
        ]);
    }

    public function createRule(): View
    {
        $this->authorize('manageRules', AttendanceOvertimeRule::class);

        return view('hrms.attendance.overtime.rule-form', [
            'rule' => null,
            'ruleTypes' => config('hrms.attendance_overtime_rule_types', []),
        ]);
    }

    public function storeRule(StoreAttendanceOvertimeRuleRequest $request): RedirectResponse
    {
        $this->overtimeService->createRule($request->overtimeRuleData(), $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.rules')
            ->with('status', __('attendance.overtime.rule_created'));
    }

    public function editRule(AttendanceOvertimeRule $rule): View
    {
        $this->authorize('manageRules', $rule);

        return view('hrms.attendance.overtime.rule-form', [
            'rule' => $rule,
            'ruleTypes' => config('hrms.attendance_overtime_rule_types', []),
        ]);
    }

    public function updateRule(UpdateAttendanceOvertimeRuleRequest $request, AttendanceOvertimeRule $rule): RedirectResponse
    {
        $this->overtimeService->updateRule($rule, $request->overtimeRuleData(), $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.rules')
            ->with('status', __('attendance.overtime.rule_updated'));
    }

    public function activateRule(Request $request, AttendanceOvertimeRule $rule): RedirectResponse
    {
        $this->authorize('manageRules', $rule);
        $this->overtimeService->activateRule($rule, $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.rules')
            ->with('status', __('attendance.overtime.rule_activated'));
    }

    public function deactivateRule(Request $request, AttendanceOvertimeRule $rule): RedirectResponse
    {
        $this->authorize('manageRules', $rule);
        $this->overtimeService->deactivateRule($rule, $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.rules')
            ->with('status', __('attendance.overtime.rule_deactivated'));
    }

    public function entriesIndex(Request $request): View
    {
        $this->authorize('approveOvertime', AttendanceOvertimeEntry::class);

        return view('hrms.attendance.overtime.entries', [
            'entries' => $this->listingService->paginateEntries($request),
            'statuses' => config('hrms.attendance_overtime_entry_statuses', []),
            'ruleTypes' => config('hrms.attendance_overtime_rule_types', []),
            'rules' => AttendanceOvertimeRule::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function approveEntry(ApproveAttendanceOvertimeEntryRequest $request, AttendanceOvertimeEntry $entry): RedirectResponse
    {
        $this->overtimeService->approveEntry($entry, $request->reviewData(), $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.entries', $request->query())
            ->with('status', __('attendance.overtime.approved'));
    }

    public function rejectEntry(RejectAttendanceOvertimeEntryRequest $request, AttendanceOvertimeEntry $entry): RedirectResponse
    {
        $this->overtimeService->rejectEntry($entry, $request->reviewData(), $request->user());

        return redirect()
            ->route('hrms.attendance.overtime.entries', $request->query())
            ->with('status', __('attendance.overtime.rejected'));
    }
}
