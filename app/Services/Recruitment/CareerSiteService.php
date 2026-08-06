<?php

namespace App\Services\Recruitment;

use App\Models\CareerSiteSetting;
use App\Models\CandidatePortalSetting;
use App\Models\Department;
use App\Models\JobOpening;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CareerSiteService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function getSettings(Organization $organization): CareerSiteSetting
    {
        return CareerSiteSetting::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            ['is_published' => false],
        );
    }

    public function getPortalSettings(Organization $organization): CandidatePortalSetting
    {
        return CandidatePortalSetting::query()->firstOrCreate(
            ['organization_id' => $organization->id],
            [
                'portal_enabled' => true,
                'allow_guest_apply' => true,
                'require_login_to_apply' => false,
            ],
        );
    }

    public function updateCareerSite(CareerSiteSetting $settings, array $data, User $actor, ?UploadedFile $logo = null, ?UploadedFile $banner = null): CareerSiteSetting
    {
        return DB::transaction(function () use ($settings, $data, $actor, $logo, $banner): CareerSiteSetting {
            if ($logo !== null) {
                $data['logo_path'] = $this->storeAsset($settings->organization_id, 'logo', $logo, $settings->logo_path);
            }

            if ($banner !== null) {
                $data['banner_path'] = $this->storeAsset($settings->organization_id, 'banner', $banner, $settings->banner_path);
            }

            $settings->update(array_merge($data, [
                'updated_by' => $actor->id,
                'created_by' => $settings->created_by ?? $actor->id,
            ]));

            $this->auditLogger->log($settings, 'career_site_settings_updated', [
                'is_published' => $settings->is_published,
            ], $actor);

            return $settings->fresh();
        });
    }

    public function updatePortalSettings(CandidatePortalSetting $settings, array $data, User $actor): CandidatePortalSetting
    {
        return DB::transaction(function () use ($settings, $data, $actor): CandidatePortalSetting {
            $settings->update(array_merge($data, [
                'updated_by' => $actor->id,
                'created_by' => $settings->created_by ?? $actor->id,
            ]));

            $this->auditLogger->log($settings, 'candidate_portal_settings_updated', [
                'portal_enabled' => $settings->portal_enabled,
                'allow_guest_apply' => $settings->allow_guest_apply,
                'require_login_to_apply' => $settings->require_login_to_apply,
            ], $actor);

            return $settings->fresh();
        });
    }

    /**
     * @return array{openings: Collection<int, JobOpening>, departments: Collection<int, Department>, filters: array<string, mixed>}
     */
    public function landingPageData(Organization $organization, array $filters = []): array
    {
        $query = JobOpening::query()
            ->with(['department', 'designation'])
            ->where('organization_id', $organization->id)
            ->where('status', 'published')
            ->where(function ($builder): void {
                $builder->whereNull('closing_date')
                    ->orWhereDate('closing_date', '>=', now()->toDateString());
            });

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('skills', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (! empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        $openings = $query->latest('publish_date')->get();

        $departments = Department::query()
            ->where('organization_id', $organization->id)
            ->whereIn('id', $openings->pluck('department_id')->unique())
            ->orderBy('name')
            ->get();

        return [
            'openings' => $openings,
            'departments' => $departments,
            'filters' => $filters,
            'locations' => $openings->pluck('location')->filter()->unique()->values(),
            'employment_types' => $openings->pluck('employment_type')->unique()->values(),
        ];
    }

    public function publishedOpening(Organization $organization, JobOpening $opening): JobOpening
    {
        if ((int) $opening->organization_id !== (int) $organization->id || $opening->status !== 'published') {
            abort(404);
        }

        if ($opening->closing_date && $opening->closing_date->isPast()) {
            abort(404);
        }

        return $opening->load(['department', 'designation']);
    }

    protected function storeAsset(int $organizationId, string $type, UploadedFile $file, ?string $existingPath = null): string
    {
        $disk = config('hrms.recruitment.documents.disk', 'local');
        $directory = sprintf('career-site/%d', $organizationId);

        if ($existingPath) {
            Storage::disk($disk)->delete($existingPath);
        }

        return $file->store($directory.'/'.$type, $disk);
    }
}
