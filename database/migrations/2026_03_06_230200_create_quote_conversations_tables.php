<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('new');
            $table->string('contact_phone', 30)->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_city')->nullable();
            $table->text('event_notes')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['mariachi_profile_id', 'status']);
            $table->index(['client_user_id', 'status']);
        });

        Schema::create('quote_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('message');
            $table->boolean('is_initial')->default(false);
            $table->timestamps();

            $table->index(['quote_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_messages');
        Schema::dropIfExists('quote_conversations');
    }
};
