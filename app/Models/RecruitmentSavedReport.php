<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RecruitmentSavedReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentSavedReport extends Model
{
    /** @use HasFactory<RecruitmentSavedReportFactory> */
    use Auditable, BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'report_name',
        'report_type',
        'filters_json',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportTypeLabel(): string
    {
        return config('hrms.recruitment.report_types.'.$this->report_type, $this->report_type);
    }
}
