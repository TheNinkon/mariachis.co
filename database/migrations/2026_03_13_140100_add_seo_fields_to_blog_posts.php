<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('meta_title')->nullable()->after('slug');
            $table->text('meta_description')->nullable()->after('excerpt');
            $table->string('og_image')->nullable()->after('featured_image');
            $table->string('robots')->nullable()->after('og_image');
            $table->string('canonical_override')->nullable()->after('robots');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'og_image',
                'robots',
                'canonical_override',
            ]);
        });
    }
};
