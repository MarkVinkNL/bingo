<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Default admin credentials for Filament panel access.
     */
    public const DEFAULT_EMAIL = 'mark@studionox.nl';

    public const DEFAULT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => self::DEFAULT_EMAIL],
            [
                'name' => 'Admin',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => UserRole::Superadmin,
            ]
        );
    }
}
