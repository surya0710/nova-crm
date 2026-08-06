<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\UploadTaxProofRequest;
use App\Http\Requests\Hrms\VerifyTaxProofRequest;
use App\Models\TaxDeclaration;
use App\Models\TaxProof;
use App\Services\Hrms\TaxFacadeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxProofController extends Controller
{
    public function __construct(protected TaxFacadeService $taxFacade) {}

    public function index(): View
    {
        $this->authorize('viewAny', TaxProof::class);

        return view('hrms.payroll.tax.proofs.index', [
            'proofs' => TaxProof::query()
                ->with(['employee', 'declaration', 'item', 'uploadedBy'])
                ->latest()
                ->paginate(20),
            'declarations' => TaxDeclaration::query()
                ->with('employee')
                ->whereIn('status', [TaxDeclaration::STATUS_SUBMITTED, TaxDeclaration::STATUS_VERIFIED])
                ->latest()
                ->limit(200)
                ->get(),
        ]);
    }

    public function store(UploadTaxProofRequest $request): RedirectResponse
    {
        $declaration = TaxDeclaration::query()->findOrFail($request->integer('tax_declaration_id'));

        $this->taxFacade->uploadProof(
            $declaration,
            $request->safe()->except(['file']),
            $request->file('file'),
            $request->user(),
        );

        return redirect()->route('hrms.payroll.tax.proofs.index')
            ->with('status', 'hrms-tax-proof-uploaded');
    }

    public function verify(VerifyTaxProofRequest $request, TaxProof $proof): RedirectResponse
    {
        $this->taxFacade->verifyProof(
            $proof,
            (float) $request->validated('approved_amount'),
            $request->validated('comments'),
            $request->user(),
        );

        return redirect()->route('hrms.payroll.tax.proofs.index')
            ->with('status', 'hrms-tax-proof-verified');
    }

    public function reject(Request $request, TaxProof $proof): RedirectResponse
    {
        $this->authorize('reject', $proof);

        $request->validate(['comments' => ['required', 'string', 'max:1000']]);

        $this->taxFacade->rejectProof($proof, $request->input('comments'), $request->user());

        return redirect()->route('hrms.payroll.tax.proofs.index')
            ->with('status', 'hrms-tax-proof-rejected');
    }
}
