<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->string('operation_type', 24)->default('initial')->after('status');
            $table->foreignId('retry_of_payment_id')->nullable()->after('operation_type')->constrained('listing_payments')->nullOnDelete();
            $table->string('source_plan_code', 50)->nullable()->after('retry_of_payment_id');
            $table->string('target_plan_code', 50)->nullable()->after('source_plan_code');
            $table->unsignedInteger('subtotal_amount_cop')->nullable()->after('target_plan_code');
            $table->unsignedInteger('discount_amount_cop')->default(0)->after('subtotal_amount_cop');
            $table->unsignedInteger('base_amount_cop')->nullable()->after('discount_amount_cop');
            $table->unsignedInteger('applied_credit_cop')->default(0)->after('base_amount_cop');
            $table->unsignedInteger('final_amount_cop')->nullable()->after('applied_credit_cop');
            $table->json('operation_metadata')->nullable()->after('provider_payload');
        });

        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->index(['operation_type', 'status'], 'listing_payments_operation_status_idx');
            $table->index(['target_plan_code', 'status'], 'listing_payments_target_plan_status_idx');
        });

        DB::table('listing_payments')->update([
            'target_plan_code' => DB::raw('plan_code'),
            'subtotal_amount_cop' => DB::raw('amount_cop'),
            'base_amount_cop' => DB::raw('amount_cop'),
            'final_amount_cop' => DB::raw('amount_cop'),
        ]);
    }

    public function down(): void
    {
        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->dropIndex('listing_payments_operation_status_idx');
            $table->dropIndex('listing_payments_target_plan_status_idx');
            $table->dropConstrainedForeignId('retry_of_payment_id');
            $table->dropColumn([
                'operation_type',
                'source_plan_code',
                'target_plan_code',
                'subtotal_amount_cop',
                'discount_amount_cop',
                'base_amount_cop',
                'applied_credit_cop',
                'final_amount_cop',
                'operation_metadata',
            ]);
        });
    }
};
