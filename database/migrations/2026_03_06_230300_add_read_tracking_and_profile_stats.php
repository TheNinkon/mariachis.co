<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_messages', function (Blueprint $table): void {
            $table->timestamp('read_by_client_at')->nullable()->after('is_initial');
            $table->timestamp('read_by_mariachi_at')->nullable()->after('read_by_client_at');
        });

        Schema::create('mariachi_profile_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedBigInteger('total_favorites')->default(0);
            $table->unsignedBigInteger('total_quotes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_profile_stats');

        Schema::table('quote_messages', function (Blueprint $table): void {
            $table->dropColumn(['read_by_client_at', 'read_by_mariachi_at']);
        });
    }
};
