<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RoleAccessSmokeTest extends TestCase
{
    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@mariachis.co',
            'password' => 'Admin12345!',
        ]);

        $response->assertRedirect('/admin/dashboard');
    }

    public function test_mariachi_login_redirects_to_mariachi_panel(): void
    {
        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();

        $response = $this->post('/mariachi/login', [
            'email' => $mariachi->email,
            'password' => 'Mariachi12345!',
        ]);

        $response->assertRedirect('/mariachi/panel');
    }

    public function test_client_login_redirects_to_mi_cuenta(): void
    {
        $response = $this->post('/login', [
            'email' => 'cliente.demo@mariachis.co',
            'password' => 'Cliente12345!',
        ]);

        $response->assertRedirect('/mi-cuenta/solicitudes');
    }

    public function test_logout_redirects_by_role(): void
    {
        $client = User::query()->where('role', User::ROLE_CLIENT)->firstOrFail();
        $this->actingAs($client)->post('/auth/logout')->assertRedirect('/');

        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();
        $this->actingAs($mariachi)->post('/auth/logout')->assertRedirect('/mariachi/login');

        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->actingAs($admin)->post('/auth/logout')->assertRedirect('/admin/login');
    }
}
