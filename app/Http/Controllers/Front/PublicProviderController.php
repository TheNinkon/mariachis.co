<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use App\Services\Seo\SeoDynamicEntityService;
use App\Services\Seo\SeoResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicProviderController extends Controller
{
    public function show(
        Request $request,
        string $handle,
        SeoDynamicEntityService $dynamicSeo,
        SeoResolver $seoResolver
    ): View
    {
        $profile = MariachiProfile::query()
            ->published()
            ->with([
                'user:id,name,first_name,last_name,status,role',
                'activeListings' => function ($query): void {
                    $query->with([
                        'photos',
                        'eventTypes:id,name',
                    ])->latest('updated_at');
                },
            ])
            ->withCount('activeListings')
            ->where('slug', $handle)
            ->firstOrFail();

        $profileName = $profile->business_name ?: $profile->user?->display_name ?: 'Mariachi';
        $cityName = $profile->city_name ?: ($profile->activeListings->first()?->city_name ?: 'Colombia');
        $canonicalUrl = route('mariachi.provider.public.show', ['handle' => $profile->slug]);
        $seoTitle = $profileName.' | Perfil oficial de mariachi en '.$cityName;
        $seoDescription = $profile->short_description
            ?: 'Conoce el perfil oficial de '.$profileName.' en '.$cityName.' y revisa sus anuncios activos en Mariachis.co.';
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
                'og_image' => $profile->logo_path,
                'og_type' => 'profile',
                ]
            )),
            'canonicalUrl' => $canonicalUrl,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'verificationLabel' => $profile->verification_status === 'verified' ? 'Perfil verificado' : Str::headline((string) ($profile->verification_status ?: 'publicado')),
        ]);
    }
}
