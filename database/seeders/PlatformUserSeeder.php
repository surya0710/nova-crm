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
            ['email' => 'work.suryakantyadav@gmail.com'],
            [
                'name' => 'Platform Owner',
                'password' => Hash::make('Surya#2801'),
                'role' => 'platform-owner',
                'status' => 'active',
            ],
        );
    }
}
