<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\Mobile\SelectMyTaxRegimeRequest;
use App\Http\Requests\Hrms\Mobile\StoreMyTaxDeclarationRequest;
use App\Http\Requests\Hrms\Mobile\UploadMyTaxProofRequest;
use App\Http\Resources\Hrms\TaxResource;
use App\Models\EmployeeTaxRegime;
use App\Models\TaxDeclaration;
use App\Models\TaxProjection;
use App\Models\TaxProof;
use App\Services\Hrms\EssContext;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Services\Hrms\MobileUploadValidator;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxMeApiController extends Controller
{
    public function __construct(
        protected EssContext $essContext,
        protected HRMSApiFacadeService $facade,
        protected MobileUploadValidator $uploadValidator,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $tax = $this->facade->taxFacade();
        $fy = $tax->ensureFinancialYear($request->user());

        $regime = EmployeeTaxRegime::query()
            ->where('employee_id', $employee->id)
            ->where('tax_financial_year_id', $fy->id)
            ->latest('effective_from')
            ->first();

        $projection = TaxProjection::query()
            ->where('employee_id', $employee->id)
            ->where('tax_financial_year_id', $fy->id)
            ->latest('calculated_at')
            ->first();

        return ApiResponse::success([
            'financial_year' => [
                'id' => $fy->id,
                'name' => $fy->label ?? $fy->code,
                'code' => $fy->code ?? null,
                'start_date' => $fy->start_date?->toDateString(),
                'end_date' => $fy->end_date?->toDateString(),
            ],
            'regime' => $regime ? new TaxResource($regime) : null,
            'projection' => $projection ? new TaxResource($projection) : null,
            'widgets' => $this->facade->taxWidgets(),
        ]);
    }

    public function regimes(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', EmployeeTaxRegime::class);

        $items = EmployeeTaxRegime::query()
            ->where('employee_id', $employee->id)
            ->with('financialYear')
            ->latest('effective_from')
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $items,
            mapItem: fn (EmployeeTaxRegime $r) => (new TaxResource($r))->resolve(),
        );
    }

    public function selectRegime(SelectMyTaxRegimeRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $fy = $this->facade->taxFacade()->ensureFinancialYear($request->user());
        $record = $this->facade->taxFacade()->selectRegime(
            $employee,
            $fy,
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::success(new TaxResource($record), __('Tax regime selected.'), status: 201);
    }

    public function projections(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', TaxProjection::class);

        $items = TaxProjection::query()
            ->where('employee_id', $employee->id)
            ->with('financialYear')
            ->latest('calculated_at')
            ->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $items,
            mapItem: fn (TaxProjection $p) => (new TaxResource($p))->resolve(),
        );
    }

    public function calculateProjection(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('calculate', TaxProjection::class);

        $fy = $this->facade->taxFacade()->ensureFinancialYear($request->user());
        $projection = $this->facade->taxFacade()->projectEmployee(
            $employee,
            $fy,
            null,
            null,
            $request->user(),
        );

        return ApiResponse::success(new TaxResource($projection), __('Tax projection calculated.'), status: 201);
    }

    public function declarations(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', TaxDeclaration::class);

        $query = TaxDeclaration::query()
            ->where('employee_id', $employee->id)
            ->with(['financialYear', 'items']);

        ApiQuery::applyFilters($query, $request, ['status' => 'status']);

        $items = $query->latest()->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $items,
            mapItem: fn (TaxDeclaration $d) => (new TaxResource($d))->resolve(),
        );
    }

    public function storeDeclaration(StoreMyTaxDeclarationRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $tax = $this->facade->taxFacade();
        $fy = $request->filled('tax_financial_year_id')
            ? \App\Models\TaxFinancialYear::query()->findOrFail($request->integer('tax_financial_year_id'))
            : $tax->ensureFinancialYear($request->user());

        $declaration = $tax->createDeclaration(
            $employee,
            $fy,
            $request->validated('items'),
            $request->user(),
        );

        return ApiResponse::success(
            new TaxResource($declaration->load(['items', 'financialYear'])),
            __('Declaration created.'),
            status: 201,
        );
    }

    public function submitDeclaration(Request $request, TaxDeclaration $declaration): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        abort_unless((int) $declaration->employee_id === (int) $employee->id, 404);
        $this->authorize('submit', $declaration);

        $updated = $this->facade->taxFacade()->submitDeclaration($declaration, $request->user());

        return ApiResponse::success(
            new TaxResource($updated->load(['items', 'financialYear'])),
            __('Declaration submitted.'),
        );
    }

    public function proofs(Request $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $this->authorize('viewAny', TaxProof::class);

        $query = TaxProof::query()->where('employee_id', $employee->id);
        ApiQuery::applyFilters($query, $request, ['status' => 'status']);

        $items = $query->latest()->paginate(ApiQuery::perPage($request));

        return ApiResponse::paginated(
            $items,
            mapItem: fn (TaxProof $p) => (new TaxResource($p))->resolve(),
        );
    }

    public function storeProof(UploadMyTaxProofRequest $request): JsonResponse
    {
        $employee = $this->essContext->requireEmployee($request->user());
        $declaration = TaxDeclaration::query()->findOrFail($request->integer('tax_declaration_id'));
        abort_unless((int) $declaration->employee_id === (int) $employee->id, 404);

        $file = $request->file('file');
        if ($file) {
            $this->uploadValidator->validate($file, 'tax_proof');
        }

        $proof = $this->facade->taxFacade()->uploadProof(
            $declaration,
            $request->safe()->except(['file']),
            $file,
            $request->user(),
        );

        return ApiResponse::success(new TaxResource($proof), __('Proof uploaded.'), status: 201);
    }
}
