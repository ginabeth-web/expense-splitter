<?php

namespace App\Services;

use App\Models\ExpenseShare;
use App\Models\Group;

class BalanceCalculator
{
    /**
     * Net balance for every member: positive means the group owes them.
     *
     * @return array<int, int> [userId => cents], sorted by user id, summing to zero
     */
    public function forGroup(Group $group): array
    {
        $balances = $group->members()->orderBy('users.id')->pluck('users.id')
            ->mapWithKeys(fn ($id) => [$id => 0])
            ->all();

        $paid = $group->expenses()
            ->selectRaw('paid_by, sum(amount_cents) as total')
            ->groupBy('paid_by')
            ->pluck('total', 'paid_by');

        $owed = ExpenseShare::query()
            ->whereIn('expense_id', $group->expenses()->select('id'))
            ->selectRaw('user_id, sum(amount_cents) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        foreach ($paid as $userId => $total) {
            $balances[$userId] = ($balances[$userId] ?? 0) + (int) $total;
        }

        foreach ($owed as $userId => $total) {
            $balances[$userId] = ($balances[$userId] ?? 0) - (int) $total;
        }

        // Paying someone back counts like paying for them.
        foreach ($group->settlements()->get(['from_user_id', 'to_user_id', 'amount_cents']) as $settlement) {
            $balances[$settlement->from_user_id] = ($balances[$settlement->from_user_id] ?? 0) + $settlement->amount_cents;
            $balances[$settlement->to_user_id] = ($balances[$settlement->to_user_id] ?? 0) - $settlement->amount_cents;
        }

        ksort($balances);

        return $balances;
    }
}
