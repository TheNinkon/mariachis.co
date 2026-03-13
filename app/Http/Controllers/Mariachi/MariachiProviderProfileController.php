<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function update(Request $request): RedirectResponse
    {
        $profile = $this->providerProfile();
        $user = $request->user();

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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $logoPath = $profile->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('mariachi-provider-logos', 'public');
        }

        $profile->update([
            'business_name' => $validated['business_name'],
            'short_description' => $validated['short_description'],
            'whatsapp' => $validated['whatsapp'] ?: null,
            'website' => $validated['website'] ?: null,
            'instagram' => $validated['instagram'] ?: null,
            'facebook' => $validated['facebook'] ?: null,
            'tiktok' => $validated['tiktok'] ?: null,
            'youtube' => $validated['youtube'] ?: null,
            'logo_path' => $logoPath,
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

        return back()->with('status', 'Perfil del proveedor actualizado.');
    }

    private function providerProfile(): MariachiProfile
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->mariachiProfile()->firstOrCreate([], [
            'city_name' => null,
            'profile_completed' => false,
            'profile_completion' => 0,
            'stage_status' => 'provider_incomplete',
            'verification_status' => 'unverified',
        ]);
    }
}
