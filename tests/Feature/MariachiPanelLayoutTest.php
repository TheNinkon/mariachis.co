<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MariachiPanelLayoutTest extends TestCase
{
    public function test_mariachi_panel_uses_horizontal_layout_with_mariachi_menu(): void
    {
        $mariachi = User::query()->where('role', User::ROLE_MARIACHI)->firstOrFail();

        $this->actingAs($mariachi)
            ->get(route('mariachi.metrics'))
            ->assertOk()
            ->assertSee('data-template="horizontal-menu-template"', false)
            ->assertSee('/mariachi/anuncios', false)
            ->assertSee('Anuncios', false);
    }

    public function test_admin_panel_keeps_vertical_layout(): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-template="vertical-menu-template"', false)
            ->assertDontSee('data-template="horizontal-menu-template"', false);
    }
}
