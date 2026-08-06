<?php

namespace App\Services\Search;

use App\Models\EmployeeAsset;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class HrmsAssetSearchProvider implements SearchProviderInterface
{
    public function key(): string
    {
        return 'assets';
    }

    public function label(): string
    {
        return __('Assets');
    }

    public function search(User $user, Organization $organization, string $query, int $limit = 10): Collection
    {
        if (! $user->hasPermission('assets.view')) {
            return collect();
        }

        $query = trim($query);
        if ($query === '') {
            return collect();
        }

        return EmployeeAsset::query()
            ->where(function ($q) use ($query) {
                $q->where('asset_code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('serial_number', 'like', "%{$query}%")
                    ->orWhere('status', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(fn (EmployeeAsset $asset) => [
                'type' => __('Asset'),
                'label' => $this->label(),
                'title' => $asset->name ?? $asset->asset_code,
                'subtitle' => trim(($asset->asset_code ?? '').' · '.($asset->status ?? '')),
                'url' => route('hrms.assets.show', $asset),
                'workspace' => 'hr',
            ]);
    }
}
