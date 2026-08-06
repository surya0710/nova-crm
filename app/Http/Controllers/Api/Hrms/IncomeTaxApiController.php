<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\GenerateForm16Request;
use App\Http\Requests\Hrms\RejectTaxDeclarationRequest;
use App\Http\Requests\Hrms\SelectTaxRegimeRequest;
use App\Http\Requests\Hrms\StoreTaxDeclarationRequest;
use App\Http\Requests\Hrms\StoreTaxFinancialYearRequest;
use App\Http\Requests\Hrms\UploadTaxProofRequest;
use App\Http\Requests\Hrms\VerifyTaxProofRequest;
use App\Models\Employee;
use App\Models\EmployeeTaxRegime;
use App\Models\Form16Record;
use App\Models\TaxDeclaration;
use App\Models\TaxFinancialYear;
use App\Models\TaxProjection;
use App\Models\TaxProof;
use App\Models\TdsMonthlyCalculation;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncomeTaxApiController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function dashboard(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('tax.view')
            || $request->user()?->hasPermission('tax.manage'),
            403
        );

        $this->taxFacade->ensureFinancialYear($request->user());

        return response()->json(['data' => $this->taxFacade->dashboard()]);
    }

    public function financialYears(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxFinancialYear::class);

        return response()->json([
            'data' => TaxFinancialYear::query()
                ->withCount('slabs')
                ->orderByDesc('start_date')
                ->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function storeFinancialYear(StoreTaxFinancialYearRequest $request): JsonResponse
    {
        $fy = $this->taxFacade->createFinancialYear($request->validated(), $request->user());

        return response()->json(['message' => __('Financial year created.'), 'data' => $fy], 201);
    }

    public function regimes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmployeeTaxRegime::class);

        $fy = $this->taxFacade->ensureFinancialYear($request->user());
        $query = EmployeeTaxRegime::query()
            ->with(['employee', 'financialYear'])
            ->where('tax_financial_year_id', $fy->id);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return response()->json([
            'data' => $query->latest('effective_from')->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function selectRegime(SelectTaxRegimeRequest $request): JsonResponse
    {
        $fy = $this->taxFacade->ensureFinancialYear($request->user());
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $record = $this->taxFacade->selectRegime($employee, $fy, $request->validated(), $request->user());

        return response()->json(['message' => __('Tax regime selected.'), 'data' => $record], 201);
    }

    public function projections(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxProjection::class);

        $query = TaxProjection::query()->with(['employee', 'financialYear'])->latest('calculated_at');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('tax_financial_year_id')) {
            $query->where('tax_financial_year_id', $request->integer('tax_financial_year_id'));
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function calculateProjection(Request $request): JsonResponse
    {
        $this->authorize('calculate', TaxProjection::class);

        $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $fy = $this->taxFacade->ensureFinancialYear($request->user());
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));

        $projection = $this->taxFacade->projectEmployee($employee, $fy, null, null, $request->user());

        return response()->json(['message' => __('Tax projection calculated.'), 'data' => $projection], 201);
    }

    public function declarations(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxDeclaration::class);

        $query = TaxDeclaration::query()->with(['employee', 'financialYear', 'items'])->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function storeDeclaration(StoreTaxDeclarationRequest $request): JsonResponse
    {
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $fy = TaxFinancialYear::query()->findOrFail($request->integer('tax_financial_year_id'));

        $declaration = $this->taxFacade->createDeclaration($employee, $fy, $request->validated('items'), $request->user());

        return response()->json(['message' => __('Declaration created.'), 'data' => $declaration->load('items')], 201);
    }

    public function submitDeclaration(Request $request, TaxDeclaration $declaration): JsonResponse
    {
        $this->authorize('submit', $declaration);
        $updated = $this->taxFacade->submitDeclaration($declaration, $request->user());

        return response()->json(['message' => __('Declaration submitted.'), 'data' => $updated]);
    }

    public function verifyDeclaration(Request $request, TaxDeclaration $declaration): JsonResponse
    {
        $this->authorize('verify', $declaration);
        $updated = $this->taxFacade->verifyDeclaration($declaration, $request->user(), $request->input('comments'));

        return response()->json(['message' => __('Declaration verified.'), 'data' => $updated]);
    }

    public function rejectDeclaration(RejectTaxDeclarationRequest $request, TaxDeclaration $declaration): JsonResponse
    {
        $updated = $this->taxFacade->rejectDeclaration($declaration, $request->validated('reason'), $request->user());

        return response()->json(['message' => __('Declaration rejected.'), 'data' => $updated]);
    }

    public function proofs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxProof::class);

        $query = TaxProof::query()->with(['employee', 'declaration', 'item'])->latest();

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function storeProof(UploadTaxProofRequest $request): JsonResponse
    {
        $declaration = TaxDeclaration::query()->findOrFail($request->integer('tax_declaration_id'));

        $proof = $this->taxFacade->uploadProof(
            $declaration,
            $request->safe()->except(['file']),
            $request->file('file'),
            $request->user(),
        );

        return response()->json(['message' => __('Proof uploaded.'), 'data' => $proof], 201);
    }

    public function verifyProof(VerifyTaxProofRequest $request, TaxProof $proof): JsonResponse
    {
        $updated = $this->taxFacade->verifyProof(
            $proof,
            (float) $request->validated('approved_amount'),
            $request->validated('comments'),
            $request->user(),
        );

        return response()->json(['message' => __('Proof verified.'), 'data' => $updated]);
    }

    public function tds(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('tax.view')
            || $request->user()?->hasPermission('tax.calculate'),
            403
        );

        $query = TdsMonthlyCalculation::query()->with(['employee', 'financialYear', 'period'])->latest('calculated_at');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('tax_financial_year_id')) {
            $query->where('tax_financial_year_id', $request->integer('tax_financial_year_id'));
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('tax.view')
            || $request->user()?->hasPermission('tax.manage'),
            403
        );

        $type = $request->string('type')->toString() ?: 'tds_register';
        $financialYearId = $request->integer('tax_financial_year_id') ?: null;

        return response()->json(['data' => $this->taxFacade->report($type, $financialYearId)]);
    }

    public function exportReport(Request $request)
    {
        abort_unless(
            $request->user()?->hasPermission('tax.view')
            || $request->user()?->hasPermission('tax.manage'),
            403
        );

        $type = $request->string('type')->toString() ?: 'tds_register';
        $format = $request->string('format')->toString() ?: 'csv';
        $financialYearId = $request->integer('tax_financial_year_id') ?: null;

        $export = $this->taxFacade->exportReport($type, $format, $financialYearId);

        return Storage::disk($export['disk'])->download($export['path'], $export['filename']);
    }

    public function form16Index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Form16Record::class);

        $query = Form16Record::query()->with(['employee', 'financialYear', 'generatedBy'])->latest('generated_at');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        return response()->json([
            'data' => $query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))),
        ]);
    }

    public function generateForm16(GenerateForm16Request $request): JsonResponse
    {
        $employee = Employee::query()->findOrFail($request->integer('employee_id'));
        $fy = TaxFinancialYear::query()->findOrFail($request->integer('tax_financial_year_id'));

        $record = $this->taxFacade->generateForm16($employee, $fy, $request->user());

        return response()->json(['message' => __('Form 16 generated.'), 'data' => $record], 201);
    }
}
