<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Models\BudgetRange;
use App\Models\EventType;
use App\Models\GroupSizeOption;
use App\Models\MariachiPhoto;
use App\Models\MariachiProfile;
use App\Models\MariachiServiceArea;
use App\Models\MariachiVideo;
use App\Models\ServiceType;
use App\Services\MariachiProfileProgressService;
use App\Services\GoogleMapsSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MariachiProfileController extends Controller
{
    public function __construct(
        private readonly MariachiProfileProgressService $progressService,
        private readonly GoogleMapsSettingsService $googleMapsSettings
    )
    {
    }

    public function index(Request $request): View
    {
        $profile = $this->profile();
        $profile->load([
            'photos',
            'videos',
            'serviceAreas',
            'eventTypes:id,name',
            'serviceTypes:id,name',
            'groupSizeOptions:id,name,sort_order',
            'budgetRanges:id,name',
        ]);

        return view('content.mariachi.profile', [
            'user' => auth()->user(),
            'profile' => $profile,
            'activeSection' => (string) $request->query('section', 'datos'),
            'eventTypes' => EventType::query()->where('is_active', true)->orderBy('name')->get(),
            'serviceTypes' => ServiceType::query()->where('is_active', true)->orderBy('name')->get(),
            'groupSizeOptions' => GroupSizeOption::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'budgetRanges' => BudgetRange::query()->where('is_active', true)->orderBy('name')->get(),
            'googleMaps' => $this->googleMapsSettings->publicConfig(),
        ]);
    }

    public function updateCoreData(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:140'],
            'responsible_name' => ['required', 'string', 'max:140'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'short_description' => ['required', 'string', 'max:280'],
            'full_description' => ['required', 'string', 'max:5000'],
            'base_price' => ['required', 'numeric', 'min:0'],
        ]);

        $profile->update([
            'business_name' => $validated['business_name'],
            'responsible_name' => $validated['responsible_name'],
            'short_description' => $validated['short_description'],
            'full_description' => $validated['full_description'],
            'base_price' => $validated['base_price'],
        ]);

        auth()->user()->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $this->progressService->refresh($profile);

        return back()->with('status', 'Datos principales actualizados.');
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'whatsapp' => ['nullable', 'string', 'max:30'],
        ]);

        $profile->update([
            'whatsapp' => $validated['whatsapp'] ?? null,
        ]);

        $this->progressService->refresh($profile);

        return back()->with('status', 'WhatsApp actualizado.');
    }

    public function updateLocation(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'country' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'city_name' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $profile->update($validated);
        $this->progressService->refresh($profile);

        return back()->with('status', 'Localizacion guardada.');
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $path = $request->file('photo')->store('mariachi-photos', 'public');
        $sortOrder = ((int) $profile->photos()->max('sort_order')) + 1;

        $photo = $profile->photos()->create([
            'path' => $path,
            'title' => $validated['title'] ?? null,
            'sort_order' => $sortOrder,
            'is_featured' => $profile->photos()->count() === 0,
        ]);

        if ($photo->is_featured) {
            $profile->photos()->where('id', '!=', $photo->id)->update(['is_featured' => false]);
        }

        $this->progressService->refresh($profile);

        return back()->with('status', 'Foto cargada correctamente.');
    }

    public function deletePhoto(MariachiPhoto $photo): RedirectResponse
    {
        $profile = $this->profile();

        abort_unless($photo->mariachi_profile_id === $profile->id, 404);

        Storage::disk('public')->delete($photo->path);
        $wasFeatured = $photo->is_featured;

        $photo->delete();

        $remaining = $profile->photos()->orderBy('sort_order')->get();
        foreach ($remaining as $index => $item) {
            $item->update(['sort_order' => $index + 1]);
        }

        if ($wasFeatured) {
            $first = $profile->photos()->orderBy('sort_order')->first();
            if ($first) {
                $first->update(['is_featured' => true]);
            }
        }

        $this->progressService->refresh($profile);

        return back()->with('status', 'Foto eliminada.');
    }

    public function setFeaturedPhoto(MariachiPhoto $photo): RedirectResponse
    {
        $profile = $this->profile();
        abort_unless($photo->mariachi_profile_id === $profile->id, 404);

        $profile->photos()->update(['is_featured' => false]);
        $photo->update(['is_featured' => true]);

        return back()->with('status', 'Foto destacada actualizada.');
    }

    public function movePhoto(MariachiPhoto $photo, string $direction): RedirectResponse
    {
        $profile = $this->profile();
        abort_unless($photo->mariachi_profile_id === $profile->id, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $target = $direction === 'up'
            ? $profile->photos()->where('sort_order', '<', $photo->sort_order)->orderByDesc('sort_order')->first()
            : $profile->photos()->where('sort_order', '>', $photo->sort_order)->orderBy('sort_order')->first();

        if (! $target) {
            return back();
        }

        $originalOrder = $photo->sort_order;
        $photo->update(['sort_order' => $target->sort_order]);
        $target->update(['sort_order' => $originalOrder]);

        return back()->with('status', 'Orden de fotos actualizado.');
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:255'],
        ]);

        $platform = Str::contains($validated['url'], ['youtube.com', 'youtu.be']) ? 'youtube' : 'external';

        $profile->videos()->create([
            'url' => $validated['url'],
            'platform' => $platform,
        ]);

        $this->progressService->refresh($profile);

        return back()->with('status', 'Video agregado.');
    }

    public function deleteVideo(MariachiVideo $video): RedirectResponse
    {
        $profile = $this->profile();
        abort_unless($video->mariachi_profile_id === $profile->id, 404);

        $video->delete();
        $this->progressService->refresh($profile);

        return back()->with('status', 'Video eliminado.');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
        ]);

        $profile->update($validated);
        $this->progressService->refresh($profile);

        return back()->with('status', 'Redes sociales actualizadas.');
    }

    public function updateEvents(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'event_type_ids' => ['nullable', 'array'],
            'event_type_ids.*' => ['integer', Rule::exists('event_types', 'id')],
        ]);

        $profile->eventTypes()->sync($validated['event_type_ids'] ?? []);
        $this->progressService->refresh($profile);

        return back()->with('status', 'Tipos de evento actualizados.');
    }

    public function updateFilters(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', Rule::exists('service_types', 'id')],
            'group_size_option_ids' => ['nullable', 'array'],
            'group_size_option_ids.*' => ['integer', Rule::exists('group_size_options', 'id')],
            'budget_range_ids' => ['nullable', 'array'],
            'budget_range_ids.*' => ['integer', Rule::exists('budget_ranges', 'id')],
        ]);

        $profile->serviceTypes()->sync($validated['service_type_ids'] ?? []);
        $profile->groupSizeOptions()->sync($validated['group_size_option_ids'] ?? []);
        $profile->budgetRanges()->sync($validated['budget_range_ids'] ?? []);

        $this->progressService->refresh($profile);

        return back()->with('status', 'Filtros de anuncio actualizados.');
    }

    public function updateCoverage(Request $request): RedirectResponse
    {
        $profile = $this->profile();

        $validated = $request->validate([
            'city_name' => ['required', 'string', 'max:120'],
            'travels_to_other_cities' => ['nullable', 'boolean'],
            'additional_cities' => ['nullable', 'string', 'max:1200'],
        ]);

        $profile->update([
            'city_name' => $validated['city_name'],
            'travels_to_other_cities' => $request->boolean('travels_to_other_cities'),
        ]);

        $profile->serviceAreas()->delete();

        $cities = collect(explode(',', (string) ($validated['additional_cities'] ?? '')))
            ->map(fn (string $city): string => trim($city))
            ->filter()
            ->unique()
            ->values();

        foreach ($cities as $city) {
            $profile->serviceAreas()->create(['city_name' => $city]);
        }

        $this->progressService->refresh($profile);

        return back()->with('status', 'Cobertura guardada.');
    }

    private function profile(): MariachiProfile
    {
        $profile = auth()->user()->mariachiProfile;

        if (! $profile) {
            $profile = auth()->user()->mariachiProfile()->create([
                'city_name' => 'Pendiente',
                'profile_completed' => false,
                'profile_completion' => 0,
                'stage_status' => 'profile_incomplete',
            ]);
        }

        return $profile;
    }
}
