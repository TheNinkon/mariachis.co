<?php

namespace App\Services;

use App\Models\AccountActivationPlan;

class AccountActivationCatalogService
{
    /**
     * @return list<AccountActivationPlan>
     */
    public function plans()
    {
        return AccountActivationPlan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function activePlan(): ?AccountActivationPlan
    {
        return AccountActivationPlan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}
