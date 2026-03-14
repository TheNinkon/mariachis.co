<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiProviderProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user();
        $profile = $this->providerProfile();

        return view('content.mariachi.provider-profile', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $profile = $this->providerProfile();
        $user = $request->user();
        $canManageProfilePhoto = $profile->canManageProfilePhoto();
        $canManageProfileCover = $profile->canManageProfileCover();

        $request->merge([
            'business_name' => trim((string) $request->input('business_name', $profile->business_name)),
            'short_description' => trim((string) $request->input('short_description', $profile->short_description)),
            'email' => trim((string) $request->input('email', $user->email)),
            'phone' => trim((string) $request->input('phone', $user->phone ?? '')),
            'whatsapp' => trim((string) $request->input('whatsapp', $profile->whatsapp ?? '')),
            'website' => trim((string) $request->input('website', $profile->website ?? '')),
            'instagram' => trim((string) $request->input('instagram', $profile->instagram ?? '')),
            'facebook' => trim((string) $request->input('facebook', $profile->facebook ?? '')),
            'tiktok' => trim((string) $request->input('tiktok', $profile->tiktok ?? '')),
            'youtube' => trim((string) $request->input('youtube', $profile->youtube ?? '')),
        ]);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:140'],
            'short_description' => ['required', 'string', 'max:280'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'logo' => $canManageProfilePhoto
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']
                : ['nullable'],
            'cover' => $canManageProfileCover
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144']
                : ['nullable'],
        ]);

        if (! $canManageProfilePhoto && $request->hasFile('logo')) {
            return back()->withInput()->withErrors([
                'logo' => 'La foto de perfil se desbloquea cuando tu anuncio ya quedó pagado y validado, o cuando activas Verificación.',
            ]);
        }

        if (! $canManageProfileCover && $request->hasFile('cover')) {
            return back()->withInput()->withErrors([
                'cover' => 'La portada del perfil se desbloquea cuando tu verificación ya está activa.',
            ]);
        }

        $logoPath = $profile->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('mariachi-provider-logos', 'public');
        }

        $coverPath = $profile->cover_path;
        if ($request->hasFile('cover')) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }

            $coverPath = $request->file('cover')->store('mariachi-provider-covers', 'public');
        }

        $profile->update([
            'business_name' => $validated['business_name'],
            'short_description' => $validated['short_description'],
            'whatsapp' => ($validated['whatsapp'] ?? null) ?: null,
            'website' => ($validated['website'] ?? null) ?: null,
            'instagram' => ($validated['instagram'] ?? null) ?: null,
            'facebook' => ($validated['facebook'] ?? null) ?: null,
            'tiktok' => ($validated['tiktok'] ?? null) ?: null,
            'youtube' => ($validated['youtube'] ?? null) ?: null,
            'logo_path' => $logoPath,
            'cover_path' => $coverPath,
            'profile_completed' => true,
            'stage_status' => 'provider_ready',
        ]);

        $profile->ensureSlug();

        $user->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ]);

        if (! $profile->default_mariachi_listing_id && $profile->listings()->exists()) {
            $profile->update([
                'default_mariachi_listing_id' => $profile->listings()->latest('updated_at')->value('id'),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Perfil del proveedor actualizado.',
                'business_name' => $profile->business_name,
            ]);
        }

        return back()->with('status', 'Perfil del proveedor actualizado.');
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $profile = $user->mariachiProfile()->firstOrCreate([], [
            'business_name' => $user->display_name,
            'city_name' => null,
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
        ]);

        $shouldRefresh = false;

        if (! filled($profile->business_name)) {
            $profile->ensureBusinessNameFromUser();
            $shouldRefresh = true;
        }

        if (! filled($profile->slug) && ! $profile->slug_locked) {
            $profile->ensureSlug();
            $shouldRefresh = true;
        }

        if ($shouldRefresh) {
            $profile->refresh();
        }

        return $profile;
    }
}
