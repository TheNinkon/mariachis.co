<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'activation_token')) {
                $table->string('activation_token', 120)->nullable()->after('status');
            }

            if (! Schema::hasColumn('users', 'activation_paid_at')) {
                $table->timestamp('activation_paid_at')->nullable()->after('activation_token');
            }
        });

        if (! Schema::hasTable('account_activation_plans')) {
            Schema::create('account_activation_plans', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 140);
                $table->string('billing_type', 24)->default('one_time');
                $table->unsignedInteger('amount_cop');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('account_activation_payments')) {
            Schema::create('account_activation_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_activation_plan_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('amount_cop');
                $table->string('method', 24)->default('nequi');
                $table->string('proof_path');
                $table->string('status', 24)->default('pending_review');
                $table->string('reference_text', 120)->nullable();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status'], 'account_activation_payments_user_status_index');
            });
        }

        DB::table('account_activation_plans')->upsert([
            [
                'code' => 'ACTIVACION_CUENTA',
                'name' => 'Activacion de cuenta',
                'billing_type' => 'one_time',
                'amount_cop' => 18900,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code'], ['name', 'billing_type', 'amount_cop', 'is_active', 'sort_order', 'updated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_activation_payments');
        Schema::dropIfExists('account_activation_plans');

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('users', 'activation_token') ? 'activation_token' : null,
                Schema::hasColumn('users', 'activation_paid_at') ? 'activation_paid_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
