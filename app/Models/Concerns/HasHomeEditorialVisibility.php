<?php

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

trait HasHomeEditorialVisibility
{
    public function scopeVisibleInHome(Builder $query): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('is_visible_in_home'), true);
    }

    public function scopeAvailableForHome(Builder $query, CarbonInterface|string|null $moment = null): Builder
    {
        $currentMoment = $moment instanceof CarbonInterface
            ? $moment
            : now();

        return $query
            ->active()
            ->visibleInHome()
            ->where(function (Builder $builder) use ($currentMoment): void {
                $builder->where(function (Builder $alwaysVisible): void {
                    $alwaysVisible
                        ->whereNull($alwaysVisible->getModel()->qualifyColumn('seasonal_start_at'))
                        ->whereNull($alwaysVisible->getModel()->qualifyColumn('seasonal_end_at'));
                })->orWhere(function (Builder $seasonal) use ($currentMoment): void {
                    $seasonal
                        ->where(function (Builder $startQuery) use ($currentMoment): void {
                            $startQuery
                                ->whereNull($startQuery->getModel()->qualifyColumn('seasonal_start_at'))
                                ->orWhere($startQuery->getModel()->qualifyColumn('seasonal_start_at'), '<=', $currentMoment);
                        })
                        ->where(function (Builder $endQuery) use ($currentMoment): void {
                            $endQuery
                                ->whereNull($endQuery->getModel()->qualifyColumn('seasonal_end_at'))
                                ->orWhere($endQuery->getModel()->qualifyColumn('seasonal_end_at'), '>=', $currentMoment);
                        });
                });
            });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
