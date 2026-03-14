<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileVerificationPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileVerificationPlanController extends Controller
{
    public function index(): View
    {
        $plans = ProfileVerificationPlan::query()
            ->orderBy('sort_order')
            ->orderBy('duration_months')
            ->orderBy('id')
            ->get();

        return view('content.admin.profile-verification-plans-index', [
            'plans' => $plans,
            'totalPlans' => $plans->count(),
            'activePlans' => $plans->where('is_active', true)->count(),
            'inactivePlans' => $plans->where('is_active', false)->count(),
            'baseAmount' => (int) ($plans->min('amount_cop') ?? 0),
        ]);
    }

    public function create(): View
    {
        return view('content.admin.profile-verification-plans-form', [
            'plan' => new ProfileVerificationPlan(),
            'formAction' => route('admin.profile-verification-plans.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear plan',
            'pageTitle' => 'Nuevo plan de verificacion',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = new ProfileVerificationPlan();
        $this->fillPlan($plan, $validated, $request);

        return redirect()
            ->route('admin.profile-verification-plans.index')
            ->with('status', 'Plan de verificacion creado correctamente.');
    }

    public function edit(ProfileVerificationPlan $profileVerificationPlan): View
    {
        return view('content.admin.profile-verification-plans-form', [
            'plan' => $profileVerificationPlan,
            'formAction' => route('admin.profile-verification-plans.update', $profileVerificationPlan),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar plan de verificacion',
        ]);
    }

    public function update(Request $request, ProfileVerificationPlan $profileVerificationPlan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $profileVerificationPlan->id);

        $this->fillPlan($profileVerificationPlan, $validated, $request);

        return redirect()
            ->route('admin.profile-verification-plans.index')
            ->with('status', 'Plan de verificacion actualizado.');
    }

    public function toggleStatus(ProfileVerificationPlan $profileVerificationPlan): RedirectResponse
    {
        $profileVerificationPlan->update([
            'is_active' => ! $profileVerificationPlan->is_active,
        ]);

        return back()->with('status', 'Estado del plan de verificacion actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('profile_verification_plans', 'code')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:36'],
            'amount_cop' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillPlan(ProfileVerificationPlan $plan, array $validated, Request $request): void
    {
        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null) {
            $sortOrder = ((int) ProfileVerificationPlan::query()->max('sort_order')) + 10;
        }

        $plan->fill([
            'code' => trim((string) $validated['code']),
            'name' => trim((string) $validated['name']),
            'duration_months' => (int) $validated['duration_months'],
            'amount_cop' => (int) $validated['amount_cop'],
            'sort_order' => (int) $sortOrder,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $plan->save();
    }
}
