<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@pharmacy.com'],
            [
                'name' => 'Admin User',
                'password' => 'admin12345',
            ]
        );

        $user->syncRoles(['admin']);
    }
}
