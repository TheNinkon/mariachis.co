<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'cliente.demo@mariachis.co'],
            [
                'name' => 'Cliente Demo',
                'first_name' => 'Cliente',
                'last_name' => 'Demo',
                'phone' => '+573105551212',
                'password' => 'Cliente12345!',
                'role' => User::ROLE_CLIENT,
                'status' => User::STATUS_ACTIVE,
                'auth_provider' => 'email',
                'email_verified_at' => now(),
            ]
        );

        ClientProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['city_name' => 'Bogota']
        );
    }
}
