<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminMariachiManagementTest extends TestCase
{
    public function test_admin_can_open_mariachis_index_show_and_edit_pages(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.mariachis.index'))
            ->assertOk()
            ->assertSee('datatables-users', false);

        $this->actingAs($admin)
            ->get(route('admin.mariachis.show', $mariachi))
            ->assertOk()
            ->assertSee('datatable-listings', false);

        $this->actingAs($admin)
            ->get(route('admin.mariachis.edit', $mariachi))
            ->assertOk()
            ->assertSee('Guardar cambios', false);
    }
}
