<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function __invoke(): View
    {
        $mariachis = User::query()->where('role', User::ROLE_MARIACHI)->latest()->take(8)->get();

        return view('content.dashboard.staff', [
            'mariachis' => $mariachis,
        ]);
    }
}
