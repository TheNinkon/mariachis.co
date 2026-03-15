<?php

namespace App\Support\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAuditLogger
{
    public function log(Request $request, string $action, array $context = []): void
    {
        Log::info('admin.audit', array_merge([
            'action' => $action,
            'admin_user_id' => $request->user()?->id,
            'admin_email' => $request->user()?->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'at' => now()->toIso8601String(),
        ], $context));
    }
}
