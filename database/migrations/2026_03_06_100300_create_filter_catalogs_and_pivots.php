<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('group_size_options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('budget_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mariachi_profile_service_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_profile_id', 'service_type_id'], 'profile_service_unique');
        });

        Schema::create('budget_range_mariachi_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_range_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_profile_id', 'budget_range_id'], 'profile_budget_unique');
        });

        Schema::create('group_size_option_mariachi_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_size_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['mariachi_profile_id', 'group_size_option_id'], 'profile_group_size_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_size_option_mariachi_profile');
        Schema::dropIfExists('budget_range_mariachi_profile');
        Schema::dropIfExists('mariachi_profile_service_type');
        Schema::dropIfExists('budget_ranges');
        Schema::dropIfExists('group_size_options');
        Schema::dropIfExists('service_types');
    }
};
