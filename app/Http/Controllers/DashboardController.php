<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\BalanceCalculator;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BalanceCalculator $calculator): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'groups' => $user->groups()->orderBy('name')->get()->map(function (Group $group) use ($calculator, $user) {
                $cents = $calculator->forGroup($group)[$user->id] ?? 0;

                return [
                    ...$group->only('id', 'name', 'currency'),
                    'my_balance_cents' => $cents,
                    'my_balance_display' => Money::format(abs($cents), $group->currency),
                ];
            }),
        ]);
    }
}
