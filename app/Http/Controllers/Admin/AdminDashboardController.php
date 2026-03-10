<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalMariachis = User::query()->where('role', User::ROLE_MARIACHI)->count();
        $activeMariachis = User::query()
            ->where('role', User::ROLE_MARIACHI)
            ->where('status', User::STATUS_ACTIVE)
            ->count();
        $staffUsers = User::query()->where('role', User::ROLE_STAFF)->count();

        return view('content.dashboard.admin', [
            'totalMariachis' => $totalMariachis,
            'activeMariachis' => $activeMariachis,
            'staffUsers' => $staffUsers,
        ]);
    }
}
