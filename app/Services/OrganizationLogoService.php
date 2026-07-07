<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizationLogoService
{
    public function store(Organization $organization, UploadedFile $file): string
    {
        $this->delete($organization);

        return $file->store("organizations/{$organization->id}", 'public');
    }

    public function delete(Organization $organization): void
    {
        if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
            Storage::disk('public')->delete($organization->logo);
        }
    }
}
