<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\MariachiListing;
use App\Models\QuoteConversation;
use App\Models\QuoteMessage;
use App\Services\MariachiProfileStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteRequestController extends Controller
{
    public function store(Request $request, string $slug, MariachiProfileStatsService $statsService): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            $request->session()->put('url.intended', url()->previous() ?: route('home'));

            return redirect()->route('client.login')->withErrors([
                'auth' => 'Inicia sesión como cliente para solicitar presupuesto.',
            ]);
        }

        if (! $user->isClient()) {
            return back()->withErrors([
                'quote' => 'Solo las cuentas de cliente pueden enviar solicitudes desde esta vista.',
            ]);
        }

        $profile = MariachiListing::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'event_date' => ['nullable', 'date'],
            'event_city' => ['nullable', 'string', 'max:120'],
            'event_notes' => ['required', 'string', 'max:4000'],
        ]);

        $conversation = QuoteConversation::query()->create([
            'client_user_id' => $user->id,
            'mariachi_profile_id' => $profile->mariachi_profile_id,
            'mariachi_listing_id' => $profile->id,
            'status' => QuoteConversation::STATUS_NEW,
            'contact_phone' => $validated['contact_phone'] ?: null,
            'event_date' => $validated['event_date'] ?? null,
            'event_city' => $validated['event_city'] ?: null,
            'event_notes' => $validated['event_notes'],
            'last_message_at' => now(),
        ]);

        QuoteMessage::query()->create([
            'quote_conversation_id' => $conversation->id,
            'sender_user_id' => $user->id,
            'message' => $validated['event_notes'],
            'is_initial' => true,
            'read_by_client_at' => now(),
        ]);

        if ($profile->mariachiProfile) {
            $statsService->incrementQuotes($profile->mariachiProfile);
        }

        ClientProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'city_name' => $request->filled('event_city') ? $request->string('event_city')->toString() : $user->clientProfile?->city_name,
            ]
        );

        return redirect()->route('client.dashboard')
            ->with('status', 'Tu solicitud fue enviada. El mariachi te responderá desde el panel.');
    }
}
