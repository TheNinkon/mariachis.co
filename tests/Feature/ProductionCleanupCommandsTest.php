<?php

namespace Tests\Feature;

use App\Models\MariachiListing;
use App\Models\MariachiProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionCleanupCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_audit_command_writes_latest_reports(): void
    {
        File::delete(storage_path('app/reports/database-audit-latest.json'));
        File::delete(storage_path('app/reports/database-audit-latest.md'));

        $this->artisan('system:audit-database')
            ->expectsOutputToContain('Auditoría de base de datos generada.')
            ->assertExitCode(0);

        $this->assertFileExists(storage_path('app/reports/database-audit-latest.json'));
        $this->assertFileExists(storage_path('app/reports/database-audit-latest.md'));

        $payload = json_decode((string) File::get(storage_path('app/reports/database-audit-latest.json')), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('tables', $payload);
        $this->assertArrayHasKey('cleanup_candidates', $payload);
    }

    public function test_demo_purge_dry_run_detects_demo_profiles_and_listings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_MARIACHI,
            'status' => User::STATUS_ACTIVE,
        ]);

        $profile = MariachiProfile::query()->create([
            'user_id' => $user->id,
            'slug' => 'mariachi-demo-prueba',
            'city_name' => 'Bogota',
            'business_name' => 'Mariachi Demo Prueba',
            'responsible_name' => 'Demo',
            'short_description' => 'Perfil demo',
            'profile_completed' => true,
            'profile_completion' => 100,
            'stage_status' => 'profile_complete',
        ]);

        MariachiListing::query()->create([
            'mariachi_profile_id' => $profile->id,
            'slug' => 'mariachi-demo-prueba-listing-demo',
            'title' => 'Demo listing',
            'short_description' => 'Demo listing',
            'city_name' => 'Bogota',
            'status' => MariachiListing::STATUS_ACTIVE,
            'review_status' => MariachiListing::REVIEW_APPROVED,
            'payment_status' => MariachiListing::PAYMENT_APPROVED,
            'is_active' => true,
            'listing_completed' => true,
            'listing_completion' => 100,
        ]);

        $this->artisan('system:purge-demo', [
            '--dry-run' => true,
            '--with-profiles' => true,
        ])
            ->expectsOutputToContain('Dry-run finalizado. No se borró nada.')
            ->assertExitCode(0);

        $report = collect(File::glob(storage_path('app/reports/demo-purge-*.json')))
            ->sortDesc()
            ->first();

        $this->assertNotNull($report);

        $payload = json_decode((string) File::get($report), true);

        $this->assertSame(1, $payload['counts']['mariachi_listings']);
        $this->assertSame(1, $payload['counts']['mariachi_profiles']);
    }
}
