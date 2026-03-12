<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mariachi_profiles')
            ->where('city_name', 'Pendiente')
            ->update(['city_name' => null]);

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->string('city_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('mariachi_profiles')
            ->whereNull('city_name')
            ->update(['city_name' => 'Pendiente']);

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->string('city_name')->nullable(false)->change();
        });
    }
};
