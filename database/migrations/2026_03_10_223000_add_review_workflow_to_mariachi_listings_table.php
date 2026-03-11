<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->string('review_status', 20)->default('draft')->after('status');
            $table->timestamp('submitted_for_review_at')->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_for_review_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('reviewed_by_user_id');

            $table->index(['review_status', 'updated_at'], 'mariachi_listing_review_status_updated_idx');
            $table->index(['review_status', 'marketplace_city_id'], 'mariachi_listing_review_status_city_idx');
        });

        DB::table('mariachi_listings')
            ->where(function ($query): void {
                $query->whereIn('status', ['active', 'paused'])
                    ->orWhereNotNull('activated_at');
            })
            ->update([
                'review_status' => 'approved',
                'submitted_for_review_at' => DB::raw('COALESCE(activated_at, updated_at, created_at)'),
                'reviewed_at' => DB::raw('COALESCE(activated_at, updated_at, created_at)'),
                'rejection_reason' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $table->dropIndex('mariachi_listing_review_status_updated_idx');
            $table->dropIndex('mariachi_listing_review_status_city_idx');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn([
                'review_status',
                'submitted_for_review_at',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
