<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->string('zone_name', 120)->nullable()->after('city_name');
            $table->string('google_place_id', 191)->nullable()->after('longitude');
            $table->json('google_location_payload')->nullable()->after('google_place_id');
        });
    }

    public function down(): void
    {
        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->dropColumn([
                'zone_name',
                'google_place_id',
                'google_location_payload',
            ]);
        });
    }
};
