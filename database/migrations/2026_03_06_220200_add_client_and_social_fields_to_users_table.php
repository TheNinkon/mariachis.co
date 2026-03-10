<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('auth_provider', 40)->nullable()->after('status');
            $table->string('auth_provider_id', 191)->nullable()->after('auth_provider');
            $table->index(['auth_provider', 'auth_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['auth_provider', 'auth_provider_id']);
            $table->dropColumn(['auth_provider', 'auth_provider_id']);
        });
    }
};
