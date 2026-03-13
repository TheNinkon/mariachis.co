<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MariachiProfile;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicProviderController extends Controller
{
    public function show(string $handle): View
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
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'MusicGroup',
            'name' => $profileName,
            'description' => (string) ($profile->short_description ?: $seoDescription),
            'url' => $canonicalUrl,
            'areaServed' => array_values(array_filter([
                $cityName,
                $profile->state,
                $profile->country ?: 'Colombia',
            ])),
        ];

        if ($profile->logo_path) {
            $schema['image'] = asset('storage/'.$profile->logo_path);
        }

        if ($profile->website) {
            $schema['sameAs'][] = $profile->website;
        }
        if ($profile->instagram) {
            $schema['sameAs'][] = $profile->instagram;
        }
        if ($profile->facebook) {
            $schema['sameAs'][] = $profile->facebook;
        }
        if ($profile->tiktok) {
            $schema['sameAs'][] = $profile->tiktok;
        }
        if ($profile->youtube) {
            $schema['sameAs'][] = $profile->youtube;
        }

        return view('front.provider-show', [
            'profile' => $profile,
            'profileName' => $profileName,
            'cityName' => $cityName,
            'canonicalUrl' => $canonicalUrl,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'schemaJson' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'verificationLabel' => $profile->verification_status === 'verified' ? 'Perfil verificado' : Str::headline((string) ($profile->verification_status ?: 'publicado')),
        ]);
    }
}
