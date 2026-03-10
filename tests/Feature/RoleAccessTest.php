<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_admin_can_login_via_admin_login_and_reach_dashboard(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'Admin12345!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_mariachi_can_login_via_mariachi_login_and_reach_dashboard(): void
    {
        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();

        $response = $this->post('/mariachi/login', [
            'email' => $mariachi->email,
            'password' => 'Mariachi12345!',
        ]);

        $response->assertRedirect(route('mariachi.panel'));

        $this->actingAs($mariachi)
            ->get(route('mariachi.panel'))
            ->assertOk();
    }

    public function test_client_cannot_access_backoffice_login_flow(): void
    {
        $client = User::query()->where('role', User::ROLE_CLIENT)->firstOrFail();

        $response = $this->post('/auth/login-basic', [
            'email' => $client->email,
            'password' => 'Cliente12345!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
