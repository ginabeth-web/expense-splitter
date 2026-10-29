<?php

namespace App\Services;

use App\Exceptions\InvalidSplitException;

class SplitCalculator
{
    /**
     * Work out each person's share of an expense.
     *
     * @param  'equal'|'exact'|'percent'  $type
     * @param  array<int, int>  $inputs  equal: list of user ids; exact: [userId => cents]; percent: [userId => basis points]
     * @return array<int, int> [userId => cents], sorted by user id, summing to $amountCents
     */
    public function split(int $amountCents, string $type, array $inputs): array
    {
        if ($inputs === []) {
            throw new InvalidSplitException('Choose at least one person to share this expense.');
        }

        $shares = match ($type) {
            'equal' => $this->equal($amountCents, $inputs),
            'exact' => $this->exact($amountCents, $inputs),
            'percent' => $this->percent($amountCents, $inputs),
            default => throw new InvalidSplitException('Unknown split type.'),
        };

        ksort($shares);

        return $shares;
    }

    private function equal(int $amountCents, array $userIds): array
    {
        $userIds = array_values(array_unique($userIds));
        sort($userIds);

        $count = count($userIds);
        $base = intdiv($amountCents, $count);
        $leftover = $amountCents % $count;

        $shares = [];
        foreach ($userIds as $index => $userId) {
            $shares[$userId] = $base + ($index < $leftover ? 1 : 0);
        }

        return $shares;
    }

    private function exact(int $amountCents, array $shares): array
    {
        $this->assertNotNegative($shares);

        if (array_sum($shares) !== $amountCents) {
            throw new InvalidSplitException('The amounts must add up to the total.');
        }

        return $shares;
    }

    private function percent(int $amountCents, array $basisPoints): array
    {
        $this->assertNotNegative($basisPoints);

        if (array_sum($basisPoints) !== 10000) {
            throw new InvalidSplitException('The percentages must add up to 100.');
        }

        ksort($basisPoints);

        $shares = [];
        $remainders = [];
        foreach ($basisPoints as $userId => $points) {
            $shares[$userId] = intdiv($amountCents * $points, 10000);
            $remainders[$userId] = ($amountCents * $points) % 10000;
        }

        // Largest remainder first; ties go to the lower user id.
        uksort($remainders, fn ($a, $b) => [$remainders[$b], $a] <=> [$remainders[$a], $b]);

        $leftover = $amountCents - array_sum($shares);
        foreach (array_keys($remainders) as $userId) {
            if ($leftover-- <= 0) {
                break;
            }
            $shares[$userId]++;
        }

        return $shares;
    }

    private function assertNotNegative(array $values): void
    {
        foreach ($values as $value) {
            if ($value < 0) {
                throw new InvalidSplitException('Amounts cannot be negative.');
            }
        }
    }
}
