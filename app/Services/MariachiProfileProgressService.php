<?php

namespace App\Services;

use App\Models\MariachiProfile;

class MariachiProfileProgressService
{
    public function refresh(MariachiProfile $profile): MariachiProfile
    {
        $profile->loadMissing('user');
        $profile->ensureSlug();

        $checks = [
            'datos' => $this->hasCoreData($profile),
            'whatsapp' => filled($profile->whatsapp),
            'ubicacion' => $this->hasLocation($profile),
            'fotos' => $profile->photos()->count() > 0,
            'videos' => $profile->videos()->count() > 0,
            'redes' => $this->hasSocialData($profile),
            'eventos' => $profile->eventTypes()->count() > 0,
            'filtros' => $this->hasFilterData($profile),
            'cobertura' => $this->hasCoverage($profile),
        ];

        $completed = count(array_filter($checks));
        $total = count($checks);
        $completion = (int) round(($completed / max($total, 1)) * 100);

        $requiredForComplete =
            $checks['datos'] &&
            $checks['ubicacion'] &&
            $checks['fotos'] &&
            $checks['eventos'] &&
            $checks['filtros'] &&
            $checks['cobertura'];

        $profile->update([
            'profile_completion' => $completion,
            'profile_completed' => $requiredForComplete,
            'stage_status' => $requiredForComplete ? 'profile_complete' : 'profile_incomplete',
        ]);

        return $profile->fresh();
    }

    private function hasCoreData(MariachiProfile $profile): bool
    {
        return filled($profile->business_name)
            && filled($profile->responsible_name)
            && filled($profile->short_description)
            && filled($profile->full_description)
            && ! is_null($profile->base_price);
    }

    private function hasLocation(MariachiProfile $profile): bool
    {
        return filled($profile->country)
            && filled($profile->state)
            && filled($profile->city_name)
            && filled($profile->postal_code)
            && filled($profile->address)
            && ! is_null($profile->latitude)
            && ! is_null($profile->longitude);
    }

    private function hasSocialData(MariachiProfile $profile): bool
    {
        return filled($profile->website)
            || filled($profile->instagram)
            || filled($profile->facebook)
            || filled($profile->tiktok)
            || filled($profile->youtube);
    }

    private function hasFilterData(MariachiProfile $profile): bool
    {
        return $profile->serviceTypes()->count() > 0
            && $profile->groupSizeOptions()->count() > 0
            && $profile->budgetRanges()->count() > 0;
    }

    private function hasCoverage(MariachiProfile $profile): bool
    {
        if (! filled($profile->city_name)) {
            return false;
        }

        return $profile->travels_to_other_cities
            ? $profile->serviceAreas()->count() > 0
            : true;
    }
}
