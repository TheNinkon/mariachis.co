<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountActivationPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountActivationPlanController extends Controller
{
    public function index(): View
    {
        $plans = AccountActivationPlan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('content.admin.account-activation-plans-index', [
            'plans' => $plans,
            'totalPlans' => $plans->count(),
            'activePlans' => $plans->where('is_active', true)->count(),
            'inactivePlans' => $plans->where('is_active', false)->count(),
            'baseAmount' => (int) ($plans->min('amount_cop') ?? 0),
        ]);
    }

    public function create(): View
    {
        return view('content.admin.account-activation-plans-form', [
            'plan' => new AccountActivationPlan(),
            'formAction' => route('admin.account-activation-plans.store'),
            'formMethod' => 'POST',
            'submitLabel' => 'Crear plan',
            'pageTitle' => 'Nuevo plan de activacion',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = new AccountActivationPlan();
        $this->fillPlan($plan, $validated, $request);

        return redirect()
            ->route('admin.account-activation-plans.index')
            ->with('status', 'Plan de activacion creado correctamente.');
    }

    public function edit(AccountActivationPlan $accountActivationPlan): View
    {
        return view('content.admin.account-activation-plans-form', [
            'plan' => $accountActivationPlan,
            'formAction' => route('admin.account-activation-plans.update', $accountActivationPlan),
            'formMethod' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'pageTitle' => 'Editar plan de activacion',
        ]);
    }

    public function update(Request $request, AccountActivationPlan $accountActivationPlan): RedirectResponse
    {
        $validated = $this->validatePlan($request, $accountActivationPlan->id);

        $this->fillPlan($accountActivationPlan, $validated, $request);

        return redirect()
            ->route('admin.account-activation-plans.index')
            ->with('status', 'Plan de activacion actualizado.');
    }

    public function toggleStatus(AccountActivationPlan $accountActivationPlan): RedirectResponse
    {
        $accountActivationPlan->update([
            'is_active' => ! $accountActivationPlan->is_active,
        ]);

        if ($accountActivationPlan->fresh()->is_active) {
            AccountActivationPlan::query()
                ->where('id', '!=', $accountActivationPlan->id)
                ->update(['is_active' => false]);
        }

        return back()->with('status', 'Estado del plan de activacion actualizado.');
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
                'max:50',
                Rule::unique('account_activation_plans', 'code')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:140'],
            'billing_type' => ['required', Rule::in([AccountActivationPlan::BILLING_TYPE_ONE_TIME])],
            'amount_cop' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function fillPlan(AccountActivationPlan $plan, array $validated, Request $request): void
    {
        $sortOrder = $validated['sort_order'] ?? null;
        if ($sortOrder === null) {
            $sortOrder = ((int) AccountActivationPlan::query()->max('sort_order')) + 10;
        }

        $isActive = $request->boolean('is_active', true);

        $plan->fill([
            'code' => trim((string) $validated['code']),
            'name' => trim((string) $validated['name']),
            'billing_type' => (string) $validated['billing_type'],
            'amount_cop' => (int) $validated['amount_cop'],
            'sort_order' => (int) $sortOrder,
            'is_active' => $isActive,
        ]);

        $plan->save();

        if ($isActive) {
            AccountActivationPlan::query()
                ->where('id', '!=', $plan->id)
                ->update(['is_active' => false]);
        }
    }
}
