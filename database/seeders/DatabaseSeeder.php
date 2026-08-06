<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DynamicRbacSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Acme Corp',
            'email' => 'hello@acme.test',
        ]);

        $organization->addMember($user, 'organization-owner');
    }
}
