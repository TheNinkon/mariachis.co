<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('business_name');
        });

        DB::table('mariachi_profiles')
            ->select('id', 'business_name', 'city_name')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $profile): void {
                $base = Str::slug((string) ($profile->business_name ?: $profile->city_name ?: 'mariachi'));
                if ($base === '') {
                    $base = 'mariachi';
                }

                $candidate = $base;
                $counter = 2;

                while (
                    DB::table('mariachi_profiles')
                        ->where('slug', $candidate)
                        ->where('id', '!=', $profile->id)
                        ->exists()
                ) {
                    $candidate = $base.'-'.$counter;
                    $counter++;
                }

                DB::table('mariachi_profiles')
                    ->where('id', $profile->id)
                    ->update(['slug' => $candidate]);
            });
    }

    public function down(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
