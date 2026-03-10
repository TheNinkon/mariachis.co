<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientFavorite;
use App\Models\QuoteConversation;
use App\Models\QuoteMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ClientDashboardController extends Controller
{
    private const VALID_SECTIONS = [
        'solicitudes',
        'favoritos',
        'vistos',
        'perfil',
        'seguridad',
        'privacidad',
    ];

    public function __invoke(Request $request): View
    {
        return $this->show($request, 'solicitudes');
    }

    public function show(Request $request, string $section = 'solicitudes'): View
    {
        $user = $request->user();
        $activeSection = in_array($section, self::VALID_SECTIONS, true) ? $section : 'solicitudes';
        $statusFilter = (string) $request->query('status', 'all');
        $summaryFavoritesCount = $user->favoriteMariachiListings()->count();
        $summaryRecentViewsCount = $user->recentViews()->count();
        $summaryConversationsCount = QuoteConversation::query()
            ->forClient($user->id)
            ->count();

        $favorites = collect();
        if ($activeSection === 'favoritos') {
            $favorites = $user->favoriteMariachiListings()
                ->with(['mariachiProfile.user:id,name,first_name,last_name', 'photos'])
                ->latest('client_favorites.created_at')
                ->take(12)
                ->get();
        }

        $recentViews = collect();
        if ($activeSection === 'vistos') {
            $recentViews = $user->recentViews()
                ->with([
                    'mariachiListing.mariachiProfile.user:id,name,first_name,last_name',
                    'mariachiListing.photos',
                    'mariachiProfile.user:id,name,first_name,last_name',
                    'mariachiProfile.photos',
                ])
                ->latest('last_viewed_at')
                ->take(12)
                ->get();
        }

        $conversations = collect();
        $statusTotals = collect();
        $unreadTotal = 0;

        if ($activeSection === 'solicitudes') {
            $conversationsQuery = QuoteConversation::query()
                ->forClient($user->id)
                ->with([
                    'mariachiProfile.user:id,name,first_name,last_name,phone',
                    'mariachiProfile.photos',
                    'mariachiListing.mariachiProfile.user:id,name,first_name,last_name,phone',
                    'mariachiListing.photos',
                    'messages.sender:id,name,first_name,last_name',
                    'review.photos',
                ])
                ->withCount(['messages as unread_for_client_count' => function ($query) use ($user): void {
                    $query->where('sender_user_id', '!=', $user->id)
                        ->whereNull('read_by_client_at');
                }])
                ->withCount(['messages as mariachi_messages_count' => function ($query) use ($user): void {
                    $query->where('sender_user_id', '!=', $user->id);
                }]);

            if ($statusFilter !== 'all') {
                $conversationsQuery->where('status', $statusFilter);
            }

            $conversations = $conversationsQuery
                ->latest('last_message_at')
                ->latest('created_at')
                ->get();

            $statusTotals = QuoteConversation::query()
                ->forClient($user->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            QuoteMessage::query()
                ->whereIn('quote_conversation_id', $conversations->pluck('id'))
                ->where('sender_user_id', '!=', $user->id)
                ->whereNull('read_by_client_at')
                ->update(['read_by_client_at' => now()]);

            $unreadTotal = (int) $conversations->sum('unread_for_client_count');
        }
        $summaryUnreadCount = QuoteMessage::query()
            ->whereHas('conversation', function ($query) use ($user): void {
                $query->forClient($user->id);
            })
            ->where('sender_user_id', '!=', $user->id)
            ->whereNull('read_by_client_at')
            ->count();

        return view('front.client.dashboard', [
            'user' => $user,
            'favorites' => $favorites,
            'recentViews' => $recentViews,
            'conversations' => $conversations,
            'statusFilter' => $statusFilter,
            'statusTotals' => $statusTotals,
            'activeSection' => $activeSection,
            'unreadTotal' => $unreadTotal,
            'summaryFavoritesCount' => $summaryFavoritesCount,
            'summaryRecentViewsCount' => $summaryRecentViewsCount,
            'summaryConversationsCount' => $summaryConversationsCount,
            'summaryUnreadCount' => $summaryUnreadCount,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city_name' => ['nullable', 'string', 'max:120'],
            'zone_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?: null,
        ]);

        $user->clientProfile()->updateOrCreate([], [
            'city_name' => $validated['city_name'] ?: null,
            'zone_name' => $validated['zone_name'] ?: null,
        ]);

        return back()->with('status', 'Perfil actualizado correctamente.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('status', 'Tu contraseña fue actualizada.');
    }

    public function updatePrivacy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'share_data_for_recommendations' => ['nullable', 'boolean'],
            'share_data_for_marketing' => ['nullable', 'boolean'],
        ]);

        $request->user()->clientProfile()->updateOrCreate([], [
            'preferences' => [
                'share_data_for_recommendations' => (bool) ($validated['share_data_for_recommendations'] ?? false),
                'share_data_for_marketing' => (bool) ($validated['share_data_for_marketing'] ?? false),
            ],
        ]);

        return back()->with('status', 'Preferencias de privacidad actualizadas.');
    }

    public function deactivate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['status' => 'inactive']);
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Tu cuenta fue desactivada.');
    }

    public function removeFavorite(Request $request, int $profileId): RedirectResponse
    {
        ClientFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($profileId): void {
                $query->where('mariachi_profile_id', $profileId)
                    ->orWhere('mariachi_listing_id', $profileId);
            })
            ->delete();

        return back()->with('status', 'Anuncio eliminado de favoritos.');
    }
}
