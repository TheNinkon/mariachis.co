<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('mariachi_profiles', 'cover_path')) {
                $table->string('cover_path')->nullable()->after('logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('mariachi_profiles', 'cover_path')) {
                $table->dropColumn('cover_path');
            }
        });
    }
};
