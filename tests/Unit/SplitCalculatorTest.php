<?php

namespace Tests\Unit;

use App\Exceptions\InvalidSplitException;
use App\Services\SplitCalculator;
use PHPUnit\Framework\TestCase;

class SplitCalculatorTest extends TestCase
{
    private SplitCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SplitCalculator;
    }

    public function test_equal_split_gives_leftover_cents_to_lowest_ids(): void
    {
        $this->assertSame(
            [1 => 3334, 2 => 3333, 3 => 3333],
            $this->calculator->split(10000, 'equal', [3, 1, 2]),
        );
    }

    public function test_exact_split_returns_given_shares(): void
    {
        $this->assertSame(
            [1 => 7000, 2 => 3000],
            $this->calculator->split(10000, 'exact', [2 => 3000, 1 => 7000]),
        );
    }

    public function test_exact_split_must_sum_to_total(): void
    {
        $this->expectException(InvalidSplitException::class);
        $this->calculator->split(10000, 'exact', [1 => 7000, 2 => 2000]);
    }

    public function test_percent_split_hands_out_leftover_cents(): void
    {
        $this->assertSame(
            [1 => 5001, 2 => 5000],
            $this->calculator->split(10001, 'percent', [1 => 5000, 2 => 5000]),
        );
    }

    public function test_percent_split_always_sums_to_total(): void
    {
        $shares = $this->calculator->split(1000, 'percent', [1 => 3333, 2 => 3333, 3 => 3334]);

        $this->assertSame(1000, array_sum($shares));
        $this->assertSame([1 => 333, 2 => 333, 3 => 334], $shares);
    }

    public function test_percent_split_must_sum_to_one_hundred(): void
    {
        $this->expectException(InvalidSplitException::class);
        $this->calculator->split(10000, 'percent', [1 => 6000, 2 => 3000]);
    }

    public function test_split_needs_participants(): void
    {
        $this->expectException(InvalidSplitException::class);
        $this->calculator->split(10000, 'equal', []);
    }

    public function test_negative_inputs_are_rejected(): void
    {
        $this->expectException(InvalidSplitException::class);
        $this->calculator->split(10000, 'exact', [1 => 12000, 2 => -2000]);
    }

    public function test_unknown_split_type_is_rejected(): void
    {
        $this->expectException(InvalidSplitException::class);
        $this->calculator->split(10000, 'shares', [1]);
    }
}
