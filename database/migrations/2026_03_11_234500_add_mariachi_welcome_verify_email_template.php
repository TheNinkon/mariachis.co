<?php

use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $definition = EmailTemplateCatalog::definition(EmailTemplateCatalog::KEY_MARIACHI_WELCOME_VERIFY);

        if ($definition === null) {
            return;
        }

        DB::table('email_templates')->updateOrInsert(
            ['key' => EmailTemplateCatalog::KEY_MARIACHI_WELCOME_VERIFY],
            [
                'name' => $definition['name'],
                'audience' => $definition['audience'],
                'description' => $definition['description'],
                'subject' => $definition['subject'],
                'body_html' => $definition['body_html'],
                'variables_schema' => json_encode($definition['variables_schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'updated_by' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->where('key', EmailTemplateCatalog::KEY_MARIACHI_WELCOME_VERIFY)
            ->delete();
    }
};
