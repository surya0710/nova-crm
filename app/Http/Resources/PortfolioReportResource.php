<?php

namespace App\Http\Resources;

use App\Models\PortfolioReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PortfolioReport */
class PortfolioReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'portfolio_id' => $this->portfolio_id,
            'program_id' => $this->program_id,
            'report_type' => $this->report_type,
            'format' => $this->format,
            'generated_by' => $this->generated_by,
            'filters' => $this->filters,
            'storage_path' => $this->storage_path,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'generator' => $this->whenLoaded('generator', fn () => [
                'id' => $this->generator?->id,
                'name' => $this->generator?->name,
            ]),
            'portfolio' => $this->whenLoaded('portfolio', fn () => $this->portfolio
                ? ['id' => $this->portfolio->id, 'name' => $this->portfolio->name, 'code' => $this->portfolio->code]
                : null),
            'program' => $this->whenLoaded('program', fn () => $this->program
                ? ['id' => $this->program->id, 'name' => $this->program->name, 'code' => $this->program->code]
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
