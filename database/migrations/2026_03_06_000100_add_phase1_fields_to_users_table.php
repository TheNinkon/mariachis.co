<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('role', 30)->default('mariachi')->after('password');
            $table->string('status', 20)->default('active')->after('role');
        });

        DB::table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->lazy()
            ->each(function (object $user): void {
                $fullName = trim((string) $user->name);
                if ($fullName === '') {
                    return;
                }

                $parts = preg_split('/\s+/', $fullName);
                $firstName = $parts[0] ?? null;
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'role', 'status']);
        });
    }
};
