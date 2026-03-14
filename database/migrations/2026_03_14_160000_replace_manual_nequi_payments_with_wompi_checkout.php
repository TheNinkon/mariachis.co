<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->string('checkout_reference', 80)->nullable()->after('method');
            $table->string('provider_transaction_id', 80)->nullable()->after('checkout_reference');
            $table->string('provider_transaction_status', 32)->nullable()->after('provider_transaction_id');
            $table->json('provider_payload')->nullable()->after('provider_transaction_status');
            $table->string('method', 32)->default('wompi')->change();
            $table->string('proof_path')->nullable()->change();
        });

        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->index('checkout_reference');
            $table->index('provider_transaction_id');
        });

        Schema::table('account_activation_payments', function (Blueprint $table): void {
            $table->string('checkout_reference', 80)->nullable()->after('method');
            $table->string('provider_transaction_id', 80)->nullable()->after('checkout_reference');
            $table->string('provider_transaction_status', 32)->nullable()->after('provider_transaction_id');
            $table->json('provider_payload')->nullable()->after('provider_transaction_status');
            $table->string('method', 24)->default('wompi')->change();
            $table->string('proof_path')->nullable()->change();
        });

        Schema::table('account_activation_payments', function (Blueprint $table): void {
            $table->index('checkout_reference');
            $table->index('provider_transaction_id');
        });

        Schema::table('profile_verification_payments', function (Blueprint $table): void {
            $table->string('checkout_reference', 80)->nullable()->after('method');
            $table->string('provider_transaction_id', 80)->nullable()->after('checkout_reference');
            $table->string('provider_transaction_status', 32)->nullable()->after('provider_transaction_id');
            $table->json('provider_payload')->nullable()->after('provider_transaction_status');
            $table->string('method', 24)->default('wompi')->change();
            $table->string('proof_path')->nullable()->change();
        });

        Schema::table('profile_verification_payments', function (Blueprint $table): void {
            $table->index('checkout_reference');
            $table->index('provider_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('profile_verification_payments', function (Blueprint $table): void {
            $table->dropIndex(['checkout_reference']);
            $table->dropIndex(['provider_transaction_id']);
            $table->dropColumn([
                'checkout_reference',
                'provider_transaction_id',
                'provider_transaction_status',
                'provider_payload',
            ]);
        });

        Schema::table('account_activation_payments', function (Blueprint $table): void {
            $table->dropIndex(['checkout_reference']);
            $table->dropIndex(['provider_transaction_id']);
            $table->dropColumn([
                'checkout_reference',
                'provider_transaction_id',
                'provider_transaction_status',
                'provider_payload',
            ]);
        });

        Schema::table('listing_payments', function (Blueprint $table): void {
            $table->dropIndex(['checkout_reference']);
            $table->dropIndex(['provider_transaction_id']);
            $table->dropColumn([
                'checkout_reference',
                'provider_transaction_id',
                'provider_transaction_status',
                'provider_payload',
            ]);
        });
    }
};
