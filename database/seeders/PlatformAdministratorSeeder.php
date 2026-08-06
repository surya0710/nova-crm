<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;

/**
 * Seeds a SaaS console platform administrator (`/platform` login).
 *
 * Edit the placeholders below, then run:
 *   php artisan db:seed --class=PlatformAdministratorSeeder
 *
 * Roles (config/platform.php):
 *   - platform-owner          full access (*)
 *   - platform-administrator  operational admin permissions
 *   - platform-support        support-scoped permissions
 */
class PlatformAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        // --- Change these details before seeding ---
        $name = 'CHANGE_ME Platform Admin';
        $email = 'platform-admin@example.com';
        $password = 'CHANGE_ME_password';
        $role = 'platform-administrator'; // or platform-owner
        // -------------------------------------------

        PlatformUser::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password, // hashed by PlatformUser cast
                'role' => $role,
                'status' => 'active',
                'locked_at' => null,
                'failed_login_attempts' => 0,
            ],
        );
    }
}
