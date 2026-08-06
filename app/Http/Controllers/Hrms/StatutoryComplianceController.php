<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateStatutoryRuleSetRequest;
use App\Http\Requests\Hrms\CreateStatutoryRuleVersionRequest;
use App\Http\Requests\Hrms\UpsertEmployeeStatutoryProfileRequest;
use App\Models\Employee;
use App\Models\EmployeeStatutoryProfile;
use App\Models\StatutoryComplianceError;
use App\Models\StatutoryRuleSet;
use App\Models\StatutoryRuleVersion;
use App\Services\Hrms\StatutoryComplianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatutoryComplianceController extends Controller
{
    public function __construct(protected StatutoryComplianceService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', StatutoryRuleSet::class);

        return view('hrms.payroll.statutory.index', $this->service->dashboardStats());
    }

    public function profiles(): View
    {
        $this->authorize('viewAny', EmployeeStatutoryProfile::class);

        return view('hrms.payroll.statutory.profiles', [
            'profiles' => $this->service->listProfiles(),
            'employees' => Employee::query()->orderBy('first_name')->get(['id', 'employee_code', 'first_name', 'last_name']),
            'taxRegimes' => config('hrms.statutory.tax_regimes', []),
            'ptStates' => config('hrms.statutory.professional_tax_states', []),
        ]);
    }

    public function storeProfile(UpsertEmployeeStatutoryProfileRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        $this->service->upsertProfile($employee, $request->validated(), $request->user());

        return redirect()->route('hrms.payroll.statutory.profiles')
            ->with('status', 'hrms-statutory-profile-updated');
    }

    public function rules(): View
    {
        $this->authorize('viewAny', StatutoryRuleSet::class);

        return view('hrms.payroll.statutory.rules', [
            'ruleSets' => $this->service->listRuleSets(),
            'activeRuleSet' => $this->service->resolveActiveRuleSet(),
            'jurisdictions' => config('hrms.statutory.jurisdictions', []),
        ]);
    }

    public function storeRuleSet(CreateStatutoryRuleSetRequest $request): RedirectResponse
    {
        $this->service->createRuleSet($request->validated(), $request->user());

        return redirect()->route('hrms.payroll.statutory.rules')
            ->with('status', 'hrms-statutory-rule-set-created');
    }

    public function activateRuleSet(Request $request, StatutoryRuleSet $ruleSet): RedirectResponse
    {
        $this->authorize('activate', $ruleSet);
        $this->service->activateRuleSet($ruleSet, $request->user());

        return redirect()->route('hrms.payroll.statutory.rules')
            ->with('status', 'hrms-statutory-rule-activated');
    }

    public function seedIndia(Request $request): RedirectResponse
    {
        $this->authorize('create', StatutoryRuleSet::class);
        $this->service->ensureDefaultIndiaRuleSet($request->user());

        return redirect()->route('hrms.payroll.statutory.rules')
            ->with('status', 'hrms-statutory-india-seeded');
    }

    public function showRuleVersion(StatutoryRuleSet $ruleSet, StatutoryRuleVersion $version): View
    {
        $this->authorize('view', $ruleSet);
        abort_unless($version->statutory_rule_set_id === $ruleSet->id, 404);

        return view('hrms.payroll.statutory.version', [
            'ruleSet' => $ruleSet,
            'version' => $version,
        ]);
    }

    public function storeRuleVersion(CreateStatutoryRuleVersionRequest $request, StatutoryRuleSet $ruleSet): RedirectResponse
    {
        $this->service->createRuleVersion($ruleSet, $request->statutoryPayload(), $request->user());

        return redirect()->route('hrms.payroll.statutory.rules')
            ->with('status', 'hrms-statutory-rule-version-created');
    }

    public function validation(): View
    {
        $this->authorize('viewAny', StatutoryComplianceError::class);

        return view('hrms.payroll.statutory.validation', [
            'errors' => $this->service->listComplianceErrors(200),
            'stats' => $this->service->dashboardStats(),
        ]);
    }

    public function runValidation(Request $request): RedirectResponse
    {
        $this->authorize('validate', StatutoryComplianceError::class);
        $result = $this->service->runOrganizationValidation(actor: $request->user());

        return redirect()->route('hrms.payroll.statutory.validation')
            ->with('status', 'hrms-statutory-validation-run')
            ->with('statutory_validation_summary', [
                'validated' => $result['validated'],
                'error_count' => $result['error_count'],
            ]);
    }
}
