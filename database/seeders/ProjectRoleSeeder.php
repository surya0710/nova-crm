<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Project-level roles are config-driven (no database table).
 *
 * Membership roles are defined in config('projects.roles') and applied
 * on project_members.project_role when users join a project.
 */
class ProjectRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = config('projects.roles', []);

        if ($roles === []) {
            throw new RuntimeException('config/projects.php must define a non-empty projects.roles map.');
        }

        foreach (['owner', 'manager', 'team_member', 'viewer'] as $expectedKey) {
            if (! array_key_exists($expectedKey, $roles)) {
                throw new RuntimeException("config/projects.php roles map is missing required key [{$expectedKey}].");
            }
        }
    }
}
