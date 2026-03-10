<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\QuoteConversation;
use App\Models\QuoteMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiQuoteConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $selectedConversationId = (int) $request->query('conversation', 0);

        $baseQuery = QuoteConversation::query()
            ->forMariachiUser($user->id)
            ->with([
                'clientUser:id,name,first_name,last_name,email,phone',
                'mariachiProfile:id,user_id,business_name,slug',
                'mariachiListing:id,mariachi_profile_id,title,slug',
                'messages.sender:id,name,first_name,last_name',
            ])
            ->withCount(['messages as unread_for_mariachi_count' => function ($query) use ($user): void {
                $query->where('sender_user_id', '!=', $user->id)
                    ->whereNull('read_by_mariachi_at');
            }]);

        $conversations = $baseQuery
            ->orderByRaw("
                CASE status
                    WHEN 'new' THEN 0
                    WHEN 'in_progress' THEN 1
                    WHEN 'responded' THEN 2
                    WHEN 'closed' THEN 3
                    ELSE 4
                END
            ")
            ->latest('last_message_at')
            ->latest('created_at')
            ->get();

        $conversationIds = $conversations->pluck('id');
        if ($selectedConversationId <= 0 || ! $conversationIds->contains($selectedConversationId)) {
            $selectedConversationId = (int) ($conversationIds->first() ?? 0);
        }

        if ($selectedConversationId > 0) {
            QuoteMessage::query()
                ->where('quote_conversation_id', $selectedConversationId)
                ->where('sender_user_id', '!=', $user->id)
                ->whereNull('read_by_mariachi_at')
                ->update(['read_by_mariachi_at' => now()]);

            $selectedConversation = $conversations->firstWhere('id', $selectedConversationId);
            if ($selectedConversation) {
                $selectedConversation->setAttribute('unread_for_mariachi_count', 0);
            }
        }

        $groupedConversations = collect([
            QuoteConversation::STATUS_NEW => $conversations->where('status', QuoteConversation::STATUS_NEW)->values(),
            QuoteConversation::STATUS_IN_PROGRESS => $conversations->where('status', QuoteConversation::STATUS_IN_PROGRESS)->values(),
            QuoteConversation::STATUS_RESPONDED => $conversations->where('status', QuoteConversation::STATUS_RESPONDED)->values(),
            QuoteConversation::STATUS_CLOSED => $conversations->where('status', QuoteConversation::STATUS_CLOSED)->values(),
        ]);

        $statusTotals = $groupedConversations->map(fn ($items): int => $items->count());

        return view('content.mariachi.quote-conversations', [
            'conversations' => $conversations,
            'groupedConversations' => $groupedConversations,
            'statusTotals' => $statusTotals,
            'selectedConversationId' => $selectedConversationId,
            'isFooter' => false,
        ]);
    }

    public function reply(Request $request, QuoteConversation $conversation): RedirectResponse
    {
        $ownerId = $conversation->mariachiListing?->mariachiProfile?->user_id
            ?? $conversation->mariachiProfile?->user_id;

        abort_unless($ownerId === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        QuoteMessage::query()->create([
            'quote_conversation_id' => $conversation->id,
            'sender_user_id' => $request->user()->id,
            'message' => $validated['message'],
            'read_by_mariachi_at' => now(),
        ]);

        QuoteMessage::query()
            ->where('quote_conversation_id', $conversation->id)
            ->where('sender_user_id', '!=', $request->user()->id)
            ->whereNull('read_by_mariachi_at')
            ->update(['read_by_mariachi_at' => now()]);

        $conversation->update([
            'status' => QuoteConversation::STATUS_RESPONDED,
            'last_message_at' => now(),
        ]);

        return back()->with('status', 'Respuesta enviada al cliente.');
    }

    public function updateStatus(Request $request, QuoteConversation $conversation): RedirectResponse
    {
        $ownerId = $conversation->mariachiListing?->mariachiProfile?->user_id
            ?? $conversation->mariachiProfile?->user_id;

        abort_unless($ownerId === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                QuoteConversation::STATUS_NEW,
                QuoteConversation::STATUS_IN_PROGRESS,
                QuoteConversation::STATUS_RESPONDED,
                QuoteConversation::STATUS_CLOSED,
            ])],
        ]);

        $conversation->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Estado de solicitud actualizado.');
    }
}
