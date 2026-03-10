<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mariachi_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('mariachi_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('platform', 50)->default('external');
            $table->timestamps();
        });

        Schema::create('mariachi_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('city_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_service_areas');
        Schema::dropIfExists('mariachi_videos');
        Schema::dropIfExists('mariachi_photos');
    }
};
