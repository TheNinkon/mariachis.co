<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mariachi_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('comment');
            $table->date('event_date')->nullable();
            $table->string('event_type', 120)->nullable();

            $table->string('moderation_status', 30)->default('pending');
            $table->string('verification_status', 40)->default('basic');
            $table->boolean('is_visible')->default(false);

            $table->boolean('is_spam')->default(false);
            $table->unsignedSmallInteger('spam_score')->default(0);
            $table->boolean('has_offensive_language')->default(false);

            $table->unsignedSmallInteger('reports_count')->default(0);
            $table->text('latest_report_reason')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->text('mariachi_reply')->nullable();
            $table->timestamp('mariachi_replied_at')->nullable();
            $table->boolean('mariachi_reply_visible')->default(true);
            $table->text('mariachi_reply_moderation_note')->nullable();
            $table->foreignId('mariachi_reply_moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mariachi_reply_moderated_at')->nullable();

            $table->timestamps();

            $table->unique('quote_conversation_id');
            $table->index(['mariachi_profile_id', 'moderation_status']);
            $table->index(['mariachi_profile_id', 'is_visible']);
            $table->index(['client_user_id', 'created_at']);
            $table->index(['is_spam', 'has_offensive_language']);
        });

        Schema::create('mariachi_review_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_review_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['mariachi_review_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mariachi_review_photos');
        Schema::dropIfExists('mariachi_reviews');
    }
};
