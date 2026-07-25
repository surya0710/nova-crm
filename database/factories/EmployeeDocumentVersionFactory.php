<?php

namespace Database\Factories;

use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeDocumentVersion> */
class EmployeeDocumentVersionFactory extends Factory
{
    protected $model = EmployeeDocumentVersion::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'employee_document_id' => EmployeeDocument::factory(),
            'version_no' => 1,
            'disk' => config('hrms.documents.disk', 'local'),
            'path' => 'hrms-documents/1/1/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'uploaded_by' => User::factory(),
        ];
    }
}
