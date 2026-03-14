<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfileHandleAlias;
use App\Models\MariachiProfile;
use App\Services\Seo\SeoDynamicEntityService;
use App\Services\Seo\SeoResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicProviderController extends Controller
{
    public function show(
        Request $request,
        string $handle,
        SeoDynamicEntityService $dynamicSeo,
        SeoResolver $seoResolver,
        string $section = 'perfil'
    ): View|RedirectResponse
    {
        $section = in_array($section, ['perfil', 'anuncios', 'cobertura', 'redes'], true) ? $section : 'perfil';

        $profileQuery = MariachiProfile::query()
            ->publicPageVisible()
            ->with([
                'user:id,name,first_name,last_name,status,role,created_at',
                'serviceAreas:id,mariachi_profile_id,city_name',
                'activeListings' => function ($query): void {
                    $query->with([
                        'photos',
                        'eventTypes:id,name',
                    ])->withCount([
                        'photos',
                        'videos',
                        'serviceAreas',
                        'reviews as public_reviews_count' => function ($reviewQuery): void {
                            $reviewQuery->publicVisible();
                        },
                    ])->latest('updated_at');
                },
            ])
            ->withCount([
                'activeListings',
                'reviews as public_reviews_count' => function ($query): void {
                    $query->publicVisible();
                },
            ]);

        $profile = (clone $profileQuery)
            ->where('slug', $handle)
            ->first();

        if (! $profile) {
            $alias = MariachiProfileHandleAlias::query()
                ->where('old_slug', $handle)
                ->first();

            if ($alias) {
                $aliasedProfile = (clone $profileQuery)
                    ->whereKey($alias->mariachi_profile_id)
                    ->first();

                if ($aliasedProfile) {
                    $routeName = $section === 'perfil'
                        ? 'mariachi.provider.public.show'
                        : 'mariachi.provider.public.section';

                    $routeParameters = ['handle' => $aliasedProfile->slug];
                    if ($section !== 'perfil') {
                        $routeParameters['section'] = $section;
                    }

                    return redirect()->route($routeName, $routeParameters, 301);
                }
            }

            abort(404);
        }

        $profileName = $profile->business_name ?: $profile->user?->display_name ?: 'Mariachi';
        $cityName = $profile->city_name ?: ($profile->activeListings->first()?->city_name ?: 'Colombia');
        $canonicalUrl = $section === 'perfil'
            ? route('mariachi.provider.public.show', ['handle' => $profile->slug])
            : route('mariachi.provider.public.section', ['handle' => $profile->slug, 'section' => $section]);
        $sectionTitle = match ($section) {
            'anuncios' => 'Anuncios',
            'cobertura' => 'Cobertura',
            'redes' => 'Redes',
            default => 'Perfil oficial',
        };
        $seoTitle = $profileName.' | '.$sectionTitle.' en '.$cityName;
        $seoDescription = $profile->short_description
            ?: 'Conoce el perfil oficial de '.$profileName.' en '.$cityName.' y revisa sus anuncios activos en Mariachis.co.';
        $coverImage = $profile->shouldShowProfileCover() && filled($profile->cover_path) && Storage::disk('public')->exists($profile->cover_path)
            ? $profile->cover_path
            : null;
        $ogImage = $coverImage;

        if (! $ogImage && $profile->shouldShowProfilePhoto() && filled($profile->logo_path) && Storage::disk('public')->exists($profile->logo_path)) {
            $ogImage = $profile->logo_path;
        }

        return view('front.provider-show', [
            'profile' => $profile,
            'profileName' => $profileName,
            'cityName' => $cityName,
            'seo' => $seoResolver->resolve($request, 'profile', array_merge(
                $dynamicSeo->buildContextForEntity('profile', $profile),
                [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical' => $canonicalUrl,
                'og_image' => $ogImage,
                'og_type' => 'profile',
                ]
            )),
            'canonicalUrl' => $canonicalUrl,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'coverImage' => $coverImage,
            'activeSection' => $section,
            'verificationLabel' => $profile->hasActiveVerification()
                ? 'Perfil verificado'
                : ($profile->verification_status === 'verified'
                    ? 'Verificacion vencida'
                    : Str::headline((string) ($profile->verification_status ?: 'publicado'))),
        ]);
    }
}
