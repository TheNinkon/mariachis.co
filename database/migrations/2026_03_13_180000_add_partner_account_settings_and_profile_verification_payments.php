<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('mariachi_profiles', 'slug_locked')) {
                $table->boolean('slug_locked')->default(false)->after('slug');
            }

            if (! Schema::hasColumn('mariachi_profiles', 'verification_expires_at')) {
                $table->timestamp('verification_expires_at')->nullable()->after('verification_notes');
            }

            if (! Schema::hasColumn('mariachi_profiles', 'notification_preferences')) {
                $table->json('notification_preferences')->nullable()->after('verification_expires_at');
            }
        });

        if (! Schema::hasTable('profile_verification_payments')) {
            Schema::create('profile_verification_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
                $table->string('plan_code', 40);
                $table->unsignedTinyInteger('duration_months');
                $table->unsignedInteger('amount_cop');
                $table->string('method', 24)->default('nequi');
                $table->string('proof_path');
                $table->string('status', 24)->default('pending');
                $table->string('reference_text', 120)->nullable();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['mariachi_profile_id', 'status'], 'profile_verification_payments_profile_status_index');
            });
        }

        Schema::table('verification_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('verification_requests', 'profile_verification_payment_id')) {
                $table->foreignId('profile_verification_payment_id')
                    ->nullable()
                    ->after('mariachi_profile_id')
                    ->constrained('profile_verification_payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('verification_requests', 'profile_verification_payment_id')) {
                $table->dropConstrainedForeignId('profile_verification_payment_id');
            }
        });

        Schema::dropIfExists('profile_verification_payments');

        Schema::table('mariachi_profiles', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('mariachi_profiles', 'slug_locked') ? 'slug_locked' : null,
                Schema::hasColumn('mariachi_profiles', 'verification_expires_at') ? 'verification_expires_at' : null,
                Schema::hasColumn('mariachi_profiles', 'notification_preferences') ? 'notification_preferences' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
