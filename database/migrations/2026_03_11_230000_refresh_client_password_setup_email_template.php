<?php

use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $definition = EmailTemplateCatalog::definition(EmailTemplateCatalog::KEY_CLIENT_PASSWORD_SETUP);

        if ($definition === null) {
            return;
        }

        DB::table('email_templates')
            ->where('key', EmailTemplateCatalog::KEY_CLIENT_PASSWORD_SETUP)
            ->update([
                'name' => $definition['name'],
                'audience' => $definition['audience'],
                'description' => $definition['description'],
                'subject' => $definition['subject'],
                'body_html' => $definition['body_html'],
                'variables_schema' => json_encode($definition['variables_schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
