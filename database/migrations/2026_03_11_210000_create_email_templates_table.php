<?php

use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('audience', 30);
            $table->text('description')->nullable();
            $table->string('subject');
            $table->longText('body_html');
            $table->json('variables_schema');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        $records = collect(EmailTemplateCatalog::definitions())
            ->map(fn (array $definition): array => [
                'key' => $definition['key'],
                'name' => $definition['name'],
                'audience' => $definition['audience'],
                'description' => $definition['description'],
                'subject' => $definition['subject'],
                'body_html' => $definition['body_html'],
                'variables_schema' => json_encode($definition['variables_schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::table('email_templates')->insert($records);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
