<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StaffUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'soporte@mariachis.co'],
            [
                'name' => 'Equipo Soporte',
                'first_name' => 'Equipo',
                'last_name' => 'Soporte',
                'phone' => '+34999999999',
                'password' => 'Staff12345!',
                'role' => User::ROLE_STAFF,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );
    }
}
