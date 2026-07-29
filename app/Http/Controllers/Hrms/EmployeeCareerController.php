<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\StoreEmployeeCertificationRequest;
use App\Http\Requests\Hrms\StoreEmployeeEducationRequest;
use App\Http\Requests\Hrms\StoreEmployeeEmergencyContactRequest;
use App\Http\Requests\Hrms\StoreEmployeeExperienceRequest;
use App\Http\Requests\Hrms\StoreEmployeeSkillRequest;
use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeExperience;
use App\Models\EmployeeSkill;
use App\Services\AuditLogger;
use App\Services\Hrms\EmployeeCertificationService;
use App\Services\Hrms\EmployeeEducationService;
use App\Services\Hrms\EmployeeExperienceService;
use App\Services\Hrms\EmployeeSkillService;
use Illuminate\Http\RedirectResponse;

class EmployeeCareerController extends Controller
{
    public function __construct(
        protected EmployeeSkillService $skills,
        protected EmployeeCertificationService $certifications,
        protected EmployeeEducationService $educations,
        protected EmployeeExperienceService $experiences,
        protected AuditLogger $auditLogger,
    ) {}

    public function storeSkill(StoreEmployeeSkillRequest $request, Employee $employee): RedirectResponse
    {
        $this->skills->create($employee, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-skill-created');
    }

    public function updateSkill(StoreEmployeeSkillRequest $request, Employee $employee, EmployeeSkill $skill): RedirectResponse
    {
        abort_unless($skill->employee_id === $employee->id, 404);
        $this->skills->update($skill, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-skill-updated');
    }

    public function destroySkill(Employee $employee, EmployeeSkill $skill): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_unless($skill->employee_id === $employee->id, 404);
        $this->skills->delete($skill, request()->user());

        return back()->with('status', 'hrms-employee-skill-deleted');
    }

    public function storeCertification(StoreEmployeeCertificationRequest $request, Employee $employee): RedirectResponse
    {
        $this->certifications->create($employee, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-certification-created');
    }

    public function updateCertification(StoreEmployeeCertificationRequest $request, Employee $employee, EmployeeCertification $certification): RedirectResponse
    {
        abort_unless($certification->employee_id === $employee->id, 404);
        $this->certifications->update($certification, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-certification-updated');
    }

    public function destroyCertification(Employee $employee, EmployeeCertification $certification): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_unless($certification->employee_id === $employee->id, 404);
        $this->certifications->delete($certification, request()->user());

        return back()->with('status', 'hrms-employee-certification-deleted');
    }

    public function storeEducation(StoreEmployeeEducationRequest $request, Employee $employee): RedirectResponse
    {
        $this->educations->create($employee, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-education-created');
    }

    public function updateEducation(StoreEmployeeEducationRequest $request, Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        abort_unless($education->employee_id === $employee->id, 404);
        $this->educations->update($education, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-education-updated');
    }

    public function destroyEducation(Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_unless($education->employee_id === $employee->id, 404);
        $this->educations->delete($education, request()->user());

        return back()->with('status', 'hrms-employee-education-deleted');
    }

    public function storeExperience(StoreEmployeeExperienceRequest $request, Employee $employee): RedirectResponse
    {
        $this->experiences->create($employee, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-experience-created');
    }

    public function updateExperience(StoreEmployeeExperienceRequest $request, Employee $employee, EmployeeExperience $experience): RedirectResponse
    {
        abort_unless($experience->employee_id === $employee->id, 404);
        $this->experiences->update($experience, $request->validated(), $request->user());

        return back()->with('status', 'hrms-employee-experience-updated');
    }

    public function destroyExperience(Employee $employee, EmployeeExperience $experience): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_unless($experience->employee_id === $employee->id, 404);
        $this->experiences->delete($experience, request()->user());

        return back()->with('status', 'hrms-employee-experience-deleted');
    }

    public function storeEmergencyContact(StoreEmployeeEmergencyContactRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['is_primary'])) {
            $employee->emergencyContacts()->update(['is_primary' => false]);
        }

        $contact = $employee->emergencyContacts()->create([
            ...$data,
            'is_primary' => (bool) ($data['is_primary'] ?? $employee->emergencyContacts()->count() === 0),
        ]);

        $this->auditLogger->log($contact, 'employee_emergency_contact_created', [
            'employee_id' => $employee->id,
        ], $request->user());

        return back()->with('status', 'hrms-employee-emergency-contact-created');
    }

    public function updateEmergencyContact(StoreEmployeeEmergencyContactRequest $request, Employee $employee, EmployeeEmergencyContact $emergencyContact): RedirectResponse
    {
        abort_unless($emergencyContact->employee_id === $employee->id, 404);
        $data = $request->validated();

        if (! empty($data['is_primary'])) {
            $employee->emergencyContacts()->where('id', '!=', $emergencyContact->id)->update(['is_primary' => false]);
        }

        $emergencyContact->update($data);

        $this->auditLogger->log($emergencyContact, 'employee_emergency_contact_updated', [
            'employee_id' => $employee->id,
        ], $request->user());

        return back()->with('status', 'hrms-employee-emergency-contact-updated');
    }

    public function destroyEmergencyContact(Employee $employee, EmployeeEmergencyContact $emergencyContact): RedirectResponse
    {
        $this->authorize('update', $employee);
        abort_unless($emergencyContact->employee_id === $employee->id, 404);

        $wasPrimary = $emergencyContact->is_primary;
        $this->auditLogger->log($emergencyContact, 'employee_emergency_contact_deleted', [
            'employee_id' => $employee->id,
        ], request()->user());
        $emergencyContact->delete();

        if ($wasPrimary) {
            $employee->emergencyContacts()->first()?->update(['is_primary' => true]);
        }

        return back()->with('status', 'hrms-employee-emergency-contact-deleted');
    }
}
