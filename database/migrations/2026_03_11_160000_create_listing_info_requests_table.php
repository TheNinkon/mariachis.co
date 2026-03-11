<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_info_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mariachi_listing_id')->constrained('mariachi_listings')->cascadeOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('new');
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('phone', 40);
            $table->date('event_date')->nullable();
            $table->string('event_city', 120)->nullable();
            $table->text('message');
            $table->string('source', 40)->default('public_listing');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'status'], 'listing_info_requests_listing_status_idx');
            $table->index(['email', 'created_at'], 'listing_info_requests_email_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_info_requests');
    }
};
