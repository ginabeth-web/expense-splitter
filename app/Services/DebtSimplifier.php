<?php

namespace App\Services;

class DebtSimplifier
{
    /**
     * Turn net balances into a short list of payments that settles everyone.
     *
     * @param  array<int, int>  $balances  [userId => cents], positive = is owed
     * @return list<array{from: int, to: int, amount: int}>
     */
    public function simplify(array $balances): array
    {
        $payments = [];

        while (true) {
            $debtor = $this->extreme($balances, fn ($cents) => $cents < 0, -1);
            $creditor = $this->extreme($balances, fn ($cents) => $cents > 0, 1);

            if ($debtor === null || $creditor === null) {
                return $payments;
            }

            $amount = min(-$balances[$debtor], $balances[$creditor]);
            $payments[] = ['from' => $debtor, 'to' => $creditor, 'amount' => $amount];

            $balances[$debtor] += $amount;
            $balances[$creditor] -= $amount;
        }
    }

    /**
     * The user with the largest balance in the given direction; ties go to the lower id.
     */
    private function extreme(array $balances, callable $filter, int $direction): ?int
    {
        $best = null;

        foreach ($balances as $userId => $cents) {
            if (! $filter($cents)) {
                continue;
            }

            if ($best === null
                || $cents * $direction > $balances[$best] * $direction
                || ($cents === $balances[$best] && $userId < $best)) {
                $best = $userId;
            }
        }

        return $best;
    }
}
