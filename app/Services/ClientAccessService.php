<?php

namespace App\Services;

use App\Events\PortalAccessed;
use App\Models\ClientProjectAccess;
use App\Models\ClientUser;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientAccessService
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected PortalNotificationService $portalNotifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function invite(Organization $organization, Customer $customer, array $data, User $actor): ClientUser
    {
        if ((int) $customer->organization_id !== (int) $organization->id) {
            throw ValidationException::withMessages([
                'customer_id' => __('Customer must belong to the organization.'),
            ]);
        }

        $email = strtolower(trim((string) $data['email']));

        $existing = ClientUser::query()
            ->where('organization_id', $organization->id)
            ->where('email', $email)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => __('A client portal account already exists for this email.'),
            ]);
        }

        $password = $data['password'] ?? Str::password(12);

        $client = ClientUser::query()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'name' => $data['name'],
            'email' => $email,
            'password' => $password,
            'invited_at' => now(),
            'invited_by' => $actor->id,
            'is_active' => true,
        ]);

        $this->auditLogger->log($client, 'portal_invite', [
            'customer_id' => $customer->id,
        ], $actor);

        $this->portalNotifications->notify($client, __('Portal invitation'), __('You have been invited to the client project portal.'), null);

        return $client;
    }

    /**
     * @param  list<string>|null  $scopes
     */
    public function grantProjectAccess(ClientUser $client, Project $project, ?array $scopes, User $actor): ClientProjectAccess
    {
        if ((int) $client->organization_id !== (int) $project->organization_id) {
            throw ValidationException::withMessages([
                'project_id' => __('Project must belong to the same organization.'),
            ]);
        }

        $scopes ??= config('portal.default_share_scopes', []);

        $access = ClientProjectAccess::query()->updateOrCreate(
            [
                'client_user_id' => $client->id,
                'project_id' => $project->id,
            ],
            [
                'organization_id' => $project->organization_id,
                'scopes' => array_values(array_unique($scopes)),
                'granted_by' => $actor->id,
                'granted_at' => now(),
            ]
        );

        $this->auditLogger->log($access, 'project_shared', [
            'project_id' => $project->id,
            'scopes' => $access->scopes,
        ], $actor);

        $this->portalNotifications->notify(
            $client,
            __('Project shared'),
            __('A project has been shared with you: :name', ['name' => $project->name]),
            null
        );

        return $access->fresh(['project', 'clientUser']);
    }

    public function revokeProjectAccess(ClientUser $client, Project $project, User $actor): void
    {
        $access = ClientProjectAccess::query()
            ->where('client_user_id', $client->id)
            ->where('project_id', $project->id)
            ->first();

        if (! $access) {
            return;
        }

        $this->auditLogger->log($access, 'project_unshared', [
            'project_id' => $project->id,
        ], $actor);

        $access->delete();
    }

    public function assertCanAccessProject(ClientUser $client, Project $project, ?string $scope = null): ClientProjectAccess
    {
        $access = ClientProjectAccess::query()
            ->where('organization_id', $client->organization_id)
            ->where('client_user_id', $client->id)
            ->where('project_id', $project->id)
            ->first();

        if (! $access || ! $client->is_active) {
            abort(404);
        }

        if ($scope !== null && ! $access->allows($scope)) {
            abort(403);
        }

        return $access;
    }

    /**
     * @return Collection<int, Project>
     */
    public function accessibleProjects(ClientUser $client): Collection
    {
        return Project::query()
            ->where('organization_id', $client->organization_id)
            ->whereIn('id', $client->projectAccess()->pluck('project_id'))
            ->orderBy('name')
            ->get();
    }

    public function recordLogin(ClientUser $client): void
    {
        $client->forceFill(['last_login_at' => now()])->save();

        $runtime = app(WorkflowRuntimeContext::class);
        event(PortalAccessed::forModel(
            $client,
            ['actor_client_user_id' => $client->id],
            causationId: $runtime->causationId,
            depth: $runtime->causationId ? $runtime->depth + 1 : 0,
        ));

        $this->auditLogger->log($client, 'portal_accessed', [], null);
    }

    public function resetPassword(Organization $organization, array $data): void
    {
        $client = ClientUser::query()
            ->where('organization_id', $organization->id)
            ->where('email', strtolower(trim($data['email'])))
            ->first();

        if (! $client) {
            return;
        }

        $client->update([
            'password' => Hash::make($data['password']),
        ]);
    }
}
