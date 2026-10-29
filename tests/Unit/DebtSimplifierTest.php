<?php

namespace Tests\Unit;

use App\Services\DebtSimplifier;
use PHPUnit\Framework\TestCase;

class DebtSimplifierTest extends TestCase
{
    private function simplify(array $balances): array
    {
        return (new DebtSimplifier)->simplify($balances);
    }

    public function test_settled_group_needs_no_payments(): void
    {
        $this->assertSame([], $this->simplify([1 => 0, 2 => 0]));
    }

    public function test_one_debtor_pays_one_creditor(): void
    {
        $this->assertSame(
            [['from' => 2, 'to' => 1, 'amount' => 1000]],
            $this->simplify([1 => 1000, 2 => -1000]),
        );
    }

    public function test_two_debtors_pay_one_creditor(): void
    {
        $this->assertSame([
            ['from' => 2, 'to' => 1, 'amount' => 1000],
            ['from' => 3, 'to' => 1, 'amount' => 1000],
        ], $this->simplify([1 => 2000, 2 => -1000, 3 => -1000]));
    }

    public function test_chain_of_debts_collapses_to_one_payment(): void
    {
        $this->assertSame(
            [['from' => 1, 'to' => 3, 'amount' => 1000]],
            $this->simplify([1 => -1000, 2 => 0, 3 => 1000]),
        );
    }

    public function test_largest_debtor_pays_largest_creditor_first(): void
    {
        $this->assertSame([
            ['from' => 3, 'to' => 1, 'amount' => 5000],
            ['from' => 4, 'to' => 2, 'amount' => 2000],
            ['from' => 3, 'to' => 2, 'amount' => 1000],
        ], $this->simplify([1 => 5000, 2 => 3000, 3 => -6000, 4 => -2000]));
    }
}
