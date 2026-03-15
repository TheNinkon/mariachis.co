<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['service_types', 'group_size_options', 'budget_ranges'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('is_visible_in_home')->default(false)->after('is_active');
                $table->unsignedSmallInteger('home_priority')->default(999)->after('is_visible_in_home');
                $table->dateTime('seasonal_start_at')->nullable()->after('home_priority');
                $table->dateTime('seasonal_end_at')->nullable()->after('seasonal_start_at');
                $table->unsignedInteger('min_active_listings_required')->nullable()->after('seasonal_end_at');
                $table->unsignedInteger('home_clicks_count')->default(0)->after('min_active_listings_required');
                $table->index(['is_active', 'is_visible_in_home', 'home_priority'], $table->getTable().'_home_visibility_idx');
            });
        }

        $this->backfill('service_types', [
            'a-domicilio' => 10,
            'show-completo' => 20,
            'mariachi-por-horas' => 30,
            'serenata-sorpresa' => 40,
            'servicio-personalizado' => 50,
        ]);

        $this->backfill('group_size_options', [
            '3-integrantes' => 10,
            '4-integrantes' => 20,
            '5-integrantes' => 30,
            '7-integrantes' => 40,
            'mariachi-completo' => 50,
        ]);

        $this->backfill('budget_ranges', [
            'economico' => 10,
            'estandar' => 20,
            'premium' => 30,
        ]);
    }

    public function down(): void
    {
        foreach (['service_types', 'group_size_options', 'budget_ranges'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex($tableName.'_home_visibility_idx');
                $table->dropColumn([
                    'is_visible_in_home',
                    'home_priority',
                    'seasonal_start_at',
                    'seasonal_end_at',
                    'min_active_listings_required',
                    'home_clicks_count',
                ]);
            });
        }
    }

    /**
     * @param  array<string, int>  $priorityMap
     */
    private function backfill(string $table, array $priorityMap): void
    {
        foreach ($priorityMap as $slug => $priority) {
            DB::table($table)
                ->where('slug', $slug)
                ->update([
                    'is_visible_in_home' => true,
                    'home_priority' => $priority,
                    'updated_at' => now(),
                ]);
        }
    }
};
