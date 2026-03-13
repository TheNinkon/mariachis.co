<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_pages', function (Blueprint $table): void {
            $table->string('keywords_target')->nullable()->after('meta_description');
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('keywords_target')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('seo_pages', function (Blueprint $table): void {
            $table->dropColumn('keywords_target');
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn('keywords_target');
        });
    }
};
