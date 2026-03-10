<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mariachi_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('city_name');
            $table->string('whatsapp', 30)->nullable();
            $table->string('business_name')->nullable();
            $table->unsignedTinyInteger('profile_completion')->default(10);
            $table->boolean('profile_completed')->default(false);
            $table->string('stage_status', 30)->default('onboarding');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_profiles');
    }
};
