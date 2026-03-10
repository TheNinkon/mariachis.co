<?php

namespace Database\Seeders;

use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class MariachiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'mariachi.demo@mariachis.co'],
            [
                'name' => 'Mariachi Demo',
                'first_name' => 'Mariachi',
                'last_name' => 'Demo',
                'phone' => '+34111111111',
                'password' => 'Mariachi12345!',
                'role' => User::ROLE_MARIACHI,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        MariachiProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'city_name' => 'Madrid',
                'profile_completion' => 20,
                'profile_completed' => false,
                'stage_status' => 'onboarding',
            ]
        );
    }
}
