<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table) {
            $table->string('responsible_name')->nullable()->after('business_name');
            $table->string('short_description', 280)->nullable()->after('responsible_name');
            $table->text('full_description')->nullable()->after('short_description');
            $table->decimal('base_price', 10, 2)->nullable()->after('full_description');

            $table->string('country', 120)->nullable()->after('city_name');
            $table->string('state', 120)->nullable()->after('country');
            $table->string('postal_code', 20)->nullable()->after('state');
            $table->string('address')->nullable()->after('postal_code');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->string('website')->nullable()->after('longitude');
            $table->string('instagram')->nullable()->after('website');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('facebook');
            $table->string('youtube')->nullable()->after('tiktok');

            $table->boolean('travels_to_other_cities')->default(false)->after('youtube');
        });
    }

    public function down(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'responsible_name',
                'short_description',
                'full_description',
                'base_price',
                'country',
                'state',
                'postal_code',
                'address',
                'latitude',
                'longitude',
                'website',
                'instagram',
                'facebook',
                'tiktok',
                'youtube',
                'travels_to_other_cities',
            ]);
        });
    }
};
