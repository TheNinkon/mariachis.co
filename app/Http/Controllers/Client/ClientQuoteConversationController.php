<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\QuoteConversation;
use App\Models\QuoteMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientQuoteConversationController extends Controller
{
    public function reply(Request $request, QuoteConversation $conversation): RedirectResponse
    {
        $user = $request->user();

        abort_unless($conversation->client_user_id === $user->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        QuoteMessage::query()->create([
            'quote_conversation_id' => $conversation->id,
            'sender_user_id' => $user->id,
            'message' => $validated['message'],
            'read_by_client_at' => now(),
        ]);

        QuoteMessage::query()
            ->where('quote_conversation_id', $conversation->id)
            ->where('sender_user_id', '!=', $user->id)
            ->whereNull('read_by_client_at')
            ->update(['read_by_client_at' => now()]);

        $conversation->update([
            'status' => QuoteConversation::STATUS_IN_PROGRESS,
            'last_message_at' => now(),
        ]);

        return back()->with('status', 'Mensaje enviado al mariachi.');
    }
}
