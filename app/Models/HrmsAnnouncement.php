<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\HrmsAnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmsAnnouncement extends Model
{
    /** @use HasFactory<HrmsAnnouncementFactory> */
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $table = 'hrms_announcements';

    protected $fillable = [
        'organization_id',
        'title',
        'body',
        'target_audience',
        'start_date',
        'end_date',
        'published_at',
        'expires_at',
        'created_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function ($inner) use ($today): void {
                $inner->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($inner) use ($today): void {
                $inner->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->where(function ($inner): void {
                $inner->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($inner): void {
                $inner->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
