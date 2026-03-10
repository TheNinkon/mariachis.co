<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MariachiReview;
use App\Models\QuoteConversation;
use App\Models\User;
use App\Services\ReviewContentInspector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientReviewController extends Controller
{
    public function store(Request $request, QuoteConversation $conversation, ReviewContentInspector $inspector): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->role === User::ROLE_CLIENT, 403);
        abort_unless($conversation->client_user_id === $user->id, 403);

        if ($conversation->review()->exists()) {
            return back()->withErrors([
                'review' => 'Ya existe una opinion registrada para esta conversacion.',
            ]);
        }

        $hasMariachiReply = $conversation->messages()
            ->where('sender_user_id', '!=', $user->id)
            ->exists();

        if (! $hasMariachiReply) {
            return back()->withErrors([
                'review' => 'La opinion se habilita cuando exista al menos una respuesta del mariachi.',
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10', 'max:3000'],
            'event_date' => ['nullable', 'date'],
            'event_type' => ['nullable', 'string', 'max:120'],
            'photos' => ['nullable', 'array', 'max:'.(int) config('reviews.max_photos', 4)],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('reviews.max_photo_size_kb', 4096)],
        ]);

        $analysis = $inspector->inspect(trim((string) ($validated['title'] ?? '').' '.(string) $validated['comment']));
        $hasEvidence = ! empty($validated['photos']);

        $review = MariachiReview::query()->create([
            'quote_conversation_id' => $conversation->id,
            'client_user_id' => $user->id,
            'mariachi_profile_id' => $conversation->mariachi_profile_id,
            'mariachi_listing_id' => $conversation->mariachi_listing_id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'event_date' => $validated['event_date'] ?? null,
            'event_type' => $validated['event_type'] ?? null,
            'moderation_status' => $analysis['is_spam'] ? MariachiReview::STATUS_REPORTED : MariachiReview::STATUS_PENDING,
            'verification_status' => $hasEvidence
                ? MariachiReview::VERIFICATION_WITH_EVIDENCE
                : MariachiReview::VERIFICATION_BASIC,
            'is_visible' => false,
            'is_spam' => $analysis['is_spam'],
            'spam_score' => $analysis['spam_score'],
            'has_offensive_language' => $analysis['has_offensive_language'],
            'reports_count' => $analysis['is_spam'] ? 1 : 0,
            'latest_report_reason' => $analysis['is_spam']
                ? 'Marcada automaticamente por reglas anti-spam.'
                : null,
            'reported_at' => $analysis['is_spam'] ? now() : null,
        ]);

        foreach ((array) ($validated['photos'] ?? []) as $index => $photo) {
            $path = $photo->store('review-photos', 'public');

            $review->photos()->create([
                'path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getClientMimeType(),
                'size_bytes' => $photo->getSize(),
                'sort_order' => $index + 1,
            ]);
        }

        return back()->with('status', 'Tu opinion fue enviada y quedo pendiente de moderacion.');
    }
}
