<?php

namespace Tests\Feature;

use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MariachiProviderProfileCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_partner_can_upload_profile_cover(): void
    {
        Storage::fake('public');

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'email' => 'cover.verified@example.com',
            'phone' => '3000000000',
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'business_name' => 'Mariachi Verificado',
            'short_description' => 'Perfil listo para portada.',
            'verification_status' => 'verified',
            'verification_expires_at' => now()->addMonth(),
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'slug' => 'm-coverok12',
            'slug_locked' => false,
        ]);

        $this->actingAs($mariachi)
            ->from(route('mariachi.provider-profile.edit'))
            ->patch(route('mariachi.provider-profile.update'), [
                'business_name' => 'Mariachi Verificado',
                'short_description' => 'Perfil listo para portada.',
                'email' => 'cover.verified@example.com',
                'phone' => '3000000000',
                'whatsapp' => '3000000001',
                'cover' => UploadedFile::fake()->image('cover.jpg', 1600, 600),
            ])
            ->assertRedirect(route('mariachi.provider-profile.edit'))
            ->assertSessionHas('status');

        $profile->refresh();

        $this->assertNotNull($profile->cover_path);
        Storage::disk('public')->assertExists($profile->cover_path);
    }

    public function test_unverified_partner_cannot_upload_profile_cover(): void
    {
        Storage::fake('public');

        $mariachi = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
            'email' => 'cover.unverified@example.com',
            'phone' => '3000000000',
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $mariachi->id,
            'business_name' => 'Mariachi Sin Verificar',
            'short_description' => 'Perfil sin verificacion.',
            'verification_status' => 'unverified',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'provider_ready',
            'slug' => 'm-coverno12',
            'slug_locked' => false,
        ]);

        $this->actingAs($mariachi)
            ->from(route('mariachi.provider-profile.edit'))
            ->patch(route('mariachi.provider-profile.update'), [
                'business_name' => 'Mariachi Sin Verificar',
                'short_description' => 'Perfil sin verificacion.',
                'email' => 'cover.unverified@example.com',
                'phone' => '3000000000',
                'cover' => UploadedFile::fake()->image('cover.jpg', 1600, 600),
            ])
            ->assertRedirect(route('mariachi.provider-profile.edit'))
            ->assertSessionHasErrors('cover');

        $this->assertNull($profile->fresh()->cover_path);
    }
}
