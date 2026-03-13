<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_entity_overrides', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('robots')->nullable();
            $table->string('canonical_override')->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('keywords_target')->nullable();
            $table->longText('jsonld_override')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id'], 'seo_entity_override_unique');
            $table->index(['entity_type', 'updated_at'], 'seo_entity_override_type_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_entity_overrides');
    }
};
