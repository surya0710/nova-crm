<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmEmailTemplate extends Model
{
    use Auditable, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'subject',
        'body',
        'category',
        'is_active',
        'available_modules',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'available_modules' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        if (! filled($module)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($module) {
            $inner->whereNull('available_modules')
                ->orWhere('available_modules', '[]')
                ->orWhereJsonContains('available_modules', $module);
        });
    }

    public function categoryLabel(): string
    {
        return config('crm_email.categories.'.$this->category, ucfirst((string) $this->category));
    }

    /**
     * @return list<string>
     */
    public function variableKeys(): array
    {
        return config('crm_email.category_variables.'.$this->category, config('crm_email.category_variables.general', []));
    }
}
