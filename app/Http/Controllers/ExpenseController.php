<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function create(Group $group): Response
    {
        Gate::authorize('update', $group);

        return Inertia::render('Expenses/Create', $this->formProps($group));
    }

    public function store(ExpenseRequest $request, Group $group): RedirectResponse
    {
        DB::transaction(function () use ($request, $group) {
            $expense = $group->expenses()->create([
                ...$request->expenseAttributes(),
                'created_by' => $request->user()->id,
            ]);
            $expense->shares()->createMany($request->shareRows());
        });

        return redirect()->route('groups.show', $group)->with('success', 'Expense added.');
    }

    public function edit(Group $group, Expense $expense): Response
    {
        Gate::authorize('update', $group);

        $shares = $expense->shares()->orderBy('user_id')->get();

        return Inertia::render('Expenses/Edit', [
            ...$this->formProps($group),
            'expense' => [
                'id' => $expense->id,
                'description' => $expense->description,
                'amount' => Money::toInput($expense->amount_cents),
                'spent_on' => $expense->spent_on->toDateString(),
                'paid_by' => $expense->paid_by,
                'split_type' => $expense->split_type,
                'participants' => $shares->pluck('user_id')->all(),
                'shares' => $shares->mapWithKeys(fn ($share) => [
                    $share->user_id => Money::toInput($share->amount_cents),
                ])->all(),
                'percents' => $shares->whereNotNull('basis_points')->mapWithKeys(fn ($share) => [
                    $share->user_id => Money::toInput($share->basis_points),
                ])->all(),
            ],
        ]);
    }

    public function update(ExpenseRequest $request, Group $group, Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($request, $expense) {
            $expense->update($request->expenseAttributes());
            $expense->shares()->delete();
            $expense->shares()->createMany($request->shareRows());
        });

        return redirect()->route('groups.show', $group)->with('success', 'Expense updated.');
    }

    public function destroy(Group $group, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $group);

        $expense->delete();

        return redirect()->route('groups.show', $group)->with('success', 'Expense deleted.');
    }

    private function formProps(Group $group): array
    {
        return [
            'group' => $group->only('id', 'name', 'currency'),
            'members' => $group->members()->orderBy('name')->get()
                ->map(fn ($member) => $member->only('id', 'name')),
        ];
    }
}
