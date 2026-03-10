<?php

namespace App\Services;

use App\Models\MariachiProfile;
use App\Models\MariachiProfileStat;

class MariachiProfileStatsService
{
    public function incrementViews(MariachiProfile $profile, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $stat = $this->resolveStat($profile);
        $stat->increment('total_views', $amount);
    }

    public function incrementFavorites(MariachiProfile $profile, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $stat = $this->resolveStat($profile);
        $stat->increment('total_favorites', $amount);
    }

    public function decrementFavorites(MariachiProfile $profile, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $stat = $this->resolveStat($profile);
        $next = max(0, ((int) $stat->total_favorites) - $amount);
        $stat->update(['total_favorites' => $next]);
    }

    public function incrementQuotes(MariachiProfile $profile, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $stat = $this->resolveStat($profile);
        $stat->increment('total_quotes', $amount);
    }

    private function resolveStat(MariachiProfile $profile): MariachiProfileStat
    {
        return $profile->stat()->firstOrCreate([], [
            'total_views' => 0,
            'total_favorites' => 0,
            'total_quotes' => 0,
        ]);
    }
}
