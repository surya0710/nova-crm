<?php

namespace App\Http\Requests;

use App\Models\PortfolioReport;
use App\Services\PortfolioReportingService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePortfolioReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PortfolioReport::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = app(TenantContext::class)->id();
        $types = array_keys(config('projects.portfolio_report_types', PortfolioReportingService::REPORT_TYPES));
        $formats = array_keys(config('projects.report_formats', ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV']));

        return [
            'report_type' => ['required', 'string', Rule::in($types)],
            'format' => ['required', 'string', Rule::in($formats)],
            'portfolio_id' => [
                'nullable',
                'integer',
                Rule::exists('portfolios', 'id')->where('organization_id', $organizationId),
            ],
            'program_id' => [
                'nullable',
                'integer',
                Rule::exists('programs', 'id')->where('organization_id', $organizationId),
            ],
            'filters' => ['nullable', 'array'],
        ];
    }
}
