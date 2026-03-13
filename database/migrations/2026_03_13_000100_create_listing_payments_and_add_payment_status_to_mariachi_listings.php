<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mariachi_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mariachi_profile_id')->constrained()->cascadeOnDelete();
            $table->string('plan_code', 50);
            $table->unsignedInteger('amount_cop');
            $table->string('method', 32)->default('nequi');
            $table->string('proof_path');
            $table->string('status', 24)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('reference_text', 120)->nullable();
            $table->timestamps();

            $table->index(['mariachi_listing_id', 'status']);
            $table->index(['mariachi_profile_id', 'status']);
        });

        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->string('payment_status', 24)->default('none')->after('review_status');
        });

        DB::table('mariachi_listings')
            ->whereNotNull('selected_plan_code')
            ->update([
                'payment_status' => 'approved',
            ]);
    }

    public function down(): void
    {
        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->dropColumn('payment_status');
        });

        Schema::dropIfExists('listing_payments');
    }
};
