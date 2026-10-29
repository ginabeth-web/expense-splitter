<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Expense;
use App\Models\Group;
use App\Models\Settlement;
use App\Services\BalanceCalculator;
use App\Services\DebtSimplifier;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Groups/Index', [
            'groups' => $request->user()->groups()
                ->orderBy('name')
                ->get()
                ->map(fn (Group $group) => $group->only('id', 'name', 'currency')),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Groups/Create');
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = DB::transaction(function () use ($request) {
            $group = Group::create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
            $group->members()->attach($request->user());

            return $group;
        });

        return redirect()->route('groups.show', $group)->with('success', 'Group created.');
    }

    public function show(Group $group, BalanceCalculator $calculator, DebtSimplifier $simplifier): Response
    {
        Gate::authorize('view', $group);

        $members = $group->members()->orderBy('name')->get();
        $names = $members->pluck('name', 'id');
        $balances = $calculator->forGroup($group);

        return Inertia::render('Groups/Show', [
            'group' => $group->only('id', 'name', 'currency'),
            'members' => $members->map(fn ($member) => $member->only('id', 'name', 'email')),
            'balances' => collect($balances)->map(fn (int $cents, int $userId) => [
                'user_id' => $userId,
                'name' => $names[$userId] ?? 'Former member',
                'cents' => $cents,
                'display' => Money::format(abs($cents), $group->currency),
            ])->values(),
            'suggestions' => collect($simplifier->simplify($balances))->map(fn (array $payment) => [
                'from_id' => $payment['from'],
                'from_name' => $names[$payment['from']] ?? 'Former member',
                'to_id' => $payment['to'],
                'to_name' => $names[$payment['to']] ?? 'Former member',
                'cents' => $payment['amount'],
                'display' => Money::format($payment['amount'], $group->currency),
                'amount' => Money::toInput($payment['amount']),
            ]),
            'expenses' => $group->expenses()
                ->with('payer:id,name')
                ->orderByDesc('spent_on')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'description' => $expense->description,
                    'spent_on' => $expense->spent_on->toDateString(),
                    'payer_name' => $expense->payer->name,
                    'amount_cents' => $expense->amount_cents,
                    'amount_display' => Money::format($expense->amount_cents, $group->currency),
                ]),
            'settlements' => $group->settlements()
                ->with(['sender:id,name', 'recipient:id,name'])
                ->orderByDesc('settled_on')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Settlement $settlement) => [
                    'id' => $settlement->id,
                    'from_name' => $settlement->sender->name,
                    'to_name' => $settlement->recipient->name,
                    'settled_on' => $settlement->settled_on->toDateString(),
                    'amount_cents' => $settlement->amount_cents,
                    'amount_display' => Money::format($settlement->amount_cents, $group->currency),
                ]),
        ]);
    }
}
