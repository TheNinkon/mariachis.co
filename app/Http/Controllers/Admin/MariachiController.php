<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MariachiController extends Controller
{
    public function index(): View
    {
        $mariachis = User::query()
            ->with('mariachiProfile')
            ->where('role', User::ROLE_MARIACHI)
            ->latest()
            ->get();

        return view('content.admin.mariachis-index', [
            'mariachis' => $mariachis,
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->role !== User::ROLE_MARIACHI) {
            abort(404);
        }

        $nextStatus = $user->status === User::STATUS_ACTIVE
            ? User::STATUS_INACTIVE
            : User::STATUS_ACTIVE;

        $user->update(['status' => $nextStatus]);

        return back()->with('status', 'Estado de mariachi actualizado.');
    }
}
