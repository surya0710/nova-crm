<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Models\PayrollResult;
use Illuminate\View\View;

class PayrollResultController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', PayrollResult::class);

        return view('hrms.payroll.results.index', [
            'results' => PayrollResult::query()
                ->with(['employee', 'payrollRun.period'])
                ->latest()
                ->paginate(25),
        ]);
    }

    public function show(PayrollResult $result): View
    {
        $this->authorize('view', $result);
        $result->load(['employee', 'payrollRun.period']);

        return view('hrms.payroll.results.show', [
            'result' => $result,
        ]);
    }
}
