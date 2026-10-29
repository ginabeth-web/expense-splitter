<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Expense;
use App\Models\Group;
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

    public function show(Group $group): Response
    {
        Gate::authorize('view', $group);

        return Inertia::render('Groups/Show', [
            'group' => $group->only('id', 'name', 'currency'),
            'members' => $group->members()->orderBy('name')->get()
                ->map(fn ($member) => $member->only('id', 'name', 'email')),
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
        ]);
    }
}
