<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@mariachis.co');
        $password = env('ADMIN_PASSWORD', 'Admin12345!');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador Principal',
                'first_name' => 'Administrador',
                'last_name' => 'Principal',
                'phone' => env('ADMIN_PHONE', '+34000000000'),
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
