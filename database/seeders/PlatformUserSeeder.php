<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformUserSeeder extends Seeder
{
    public function run(): void
    {
        PlatformUser::query()->firstOrCreate(
            ['email' => 'platform@novacrm.test'],
            [
                'name' => 'Platform Owner',
                'password' => Hash::make('  '),
                'role' => 'platform-owner',
                'status' => 'active',
            ],
        );
    }
}
