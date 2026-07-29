<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\AttendanceReportFilterRequest;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Services\Hrms\AttendanceReportExportService;
use App\Services\Hrms\AttendanceReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function __construct(
        protected AttendanceReportService $reports,
        protected AttendanceReportExportService $exports,
    ) {}

    public function index(AttendanceReportFilterRequest $request): View
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $reportType = $request->input('report_type', 'monthly_attendance');
        $filters = $request->filters();

        return view('hrms.attendance.reports.index', [
            'availableReports' => $this->reports->availableReports(),
            'exportFormats' => config('hrms.attendance_reports.export_formats', []),
            'reportType' => $reportType,
            'report' => $this->reports->compile($reportType, $filters),
            'filters' => $filters,
            'departments' => $this->reports->departments(),
            'employees' => Employee::query()
                ->whereIn('status', config('hrms.clockable_employee_statuses', []))
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'department_id']),
            'monthNames' => collect(range(1, 12))->mapWithKeys(
                fn (int $month) => [$month => \Carbon\Carbon::create(null, $month, 1)->format('F')]
            ),
        ]);
    }

    public function export(AttendanceReportFilterRequest $request): StreamedResponse|BinaryFileResponse
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $reportType = $request->input('report_type', 'monthly_attendance');
        $format = $request->input('format', 'csv');

        return $this->exports->export($reportType, $format, $request->filters(), $request->user());
    }
}
