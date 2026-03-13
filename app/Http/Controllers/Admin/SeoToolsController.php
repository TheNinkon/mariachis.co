<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoRuleAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeoToolsController extends Controller
{
    public function __construct(private readonly SeoRuleAssistantService $seoRules)
    {
    }

    public function suggestCanonical(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['page', 'post', 'landing_template', 'listing', 'profile'])],
            'raw_context' => ['required', 'array'],
        ]);

        return response()->json([
            'canonical' => $this->seoRules->suggestCanonical($validated['type'], $validated['raw_context']),
        ]);
    }

    public function generateJsonLd(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['page', 'post', 'landing_template', 'listing', 'profile', 'faq'])],
            'raw_context' => ['required', 'array'],
        ]);

        return response()->json([
            'jsonld' => $this->seoRules->generateJsonLd($validated['type'], $validated['raw_context']),
        ]);
    }
}
