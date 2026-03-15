<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_types', function (Blueprint $table): void {
            $table->boolean('is_visible_in_home')->default(false)->after('is_active');
            $table->unsignedSmallInteger('home_priority')->default(999)->after('is_visible_in_home');
            $table->dateTime('seasonal_start_at')->nullable()->after('home_priority');
            $table->dateTime('seasonal_end_at')->nullable()->after('seasonal_start_at');
            $table->unsignedInteger('min_active_listings_required')->nullable()->after('seasonal_end_at');
            $table->unsignedInteger('home_clicks_count')->default(0)->after('min_active_listings_required');

            $table->index(['is_active', 'is_visible_in_home', 'home_priority'], 'event_types_home_visibility_idx');
        });

        $priorityMap = [
            'bodas' => 10,
            'cumpleanos' => 20,
            'aniversarios' => 30,
            'serenatas' => 40,
            'eventos-corporativos' => 50,
            'fiestas-privadas' => 60,
        ];

        foreach ($priorityMap as $slug => $priority) {
            DB::table('event_types')
                ->where('slug', $slug)
                ->update([
                    'is_visible_in_home' => true,
                    'home_priority' => $priority,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('event_types', function (Blueprint $table): void {
            $table->dropIndex('event_types_home_visibility_idx');
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
};
