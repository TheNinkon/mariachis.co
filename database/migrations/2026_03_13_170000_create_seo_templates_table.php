<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key')->unique();
            $table->text('title_template');
            $table->text('description_template');
            $table->string('robots')->default('index,follow');
            $table->string('og_image_path')->nullable();
            $table->string('keywords_target')->nullable();
            $table->longText('jsonld_template')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_templates');
    }
};
