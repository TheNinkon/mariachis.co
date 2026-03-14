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
            if (! Schema::hasColumn('listing_payments', 'duration_months')) {
                $table->unsignedTinyInteger('duration_months')->default(1)->after('plan_code');
            }
        });

        Schema::table('mariachi_listings', function (Blueprint $table): void {
            if (! Schema::hasColumn('mariachi_listings', 'plan_duration_months')) {
                $table->unsignedTinyInteger('plan_duration_months')->nullable()->after('selected_plan_code');
            }

            if (! Schema::hasColumn('mariachi_listings', 'plan_expires_at')) {
                $table->timestamp('plan_expires_at')->nullable()->after('activated_at');
            }
        });

        if (Schema::hasTable('listing_payments')) {
            DB::table('listing_payments')
                ->whereNull('duration_months')
                ->update(['duration_months' => 1]);
        }

        if (Schema::hasTable('mariachi_listings')) {
            DB::table('mariachi_listings')
                ->whereNotNull('selected_plan_code')
                ->whereNull('plan_duration_months')
                ->update(['plan_duration_months' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('listing_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('listing_payments', 'duration_months')) {
                $table->dropColumn('duration_months');
            }
        });

        Schema::table('mariachi_listings', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('mariachi_listings', 'plan_duration_months') ? 'plan_duration_months' : null,
                Schema::hasColumn('mariachi_listings', 'plan_expires_at') ? 'plan_expires_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
