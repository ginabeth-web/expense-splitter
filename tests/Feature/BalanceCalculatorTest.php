<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    private User $carol;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->alice, $this->bob, $this->carol] = User::factory()->count(3)->create()->all();
        $this->group = Group::factory()->create(['created_by' => $this->alice->id]);
        $this->group->members()->attach([$this->bob->id, $this->carol->id]);
    }

    /** @param array<int, int> $shares */
    private function expense(User $payer, int $amountCents, array $shares): Expense
    {
        $expense = Expense::factory()->for($this->group)->create([
            'paid_by' => $payer->id,
            'amount_cents' => $amountCents,
        ]);

        foreach ($shares as $userId => $cents) {
            $expense->shares()->create(['user_id' => $userId, 'amount_cents' => $cents]);
        }

        return $expense;
    }

    private function balances(): array
    {
        return app(BalanceCalculator::class)->forGroup($this->group);
    }

    public function test_payer_is_owed_what_others_share(): void
    {
        $this->expense($this->alice, 30000, [
            $this->alice->id => 10000,
            $this->bob->id => 10000,
            $this->carol->id => 10000,
        ]);

        $this->assertSame([
            $this->alice->id => 20000,
            $this->bob->id => -10000,
            $this->carol->id => -10000,
        ], $this->balances());
    }

    public function test_balances_combine_across_expenses_and_sum_to_zero(): void
    {
        $this->expense($this->alice, 30000, [
            $this->alice->id => 10000,
            $this->bob->id => 10000,
            $this->carol->id => 10000,
        ]);
        $this->expense($this->bob, 9000, [
            $this->bob->id => 4500,
            $this->carol->id => 4500,
        ]);

        $balances = $this->balances();

        $this->assertSame([
            $this->alice->id => 20000,
            $this->bob->id => -5500,
            $this->carol->id => -14500,
        ], $balances);
        $this->assertSame(0, array_sum($balances));
    }

    public function test_member_without_expenses_has_zero_balance(): void
    {
        $this->assertSame([
            $this->alice->id => 0,
            $this->bob->id => 0,
            $this->carol->id => 0,
        ], $this->balances());
    }

    public function test_settlements_move_balances(): void
    {
        $this->expense($this->alice, 30000, [
            $this->alice->id => 10000,
            $this->bob->id => 10000,
            $this->carol->id => 10000,
        ]);
        $this->group->settlements()->create([
            'from_user_id' => $this->bob->id,
            'to_user_id' => $this->alice->id,
            'amount_cents' => 10000,
            'settled_on' => '2026-10-29',
            'created_by' => $this->bob->id,
        ]);

        $this->assertSame([
            $this->alice->id => 10000,
            $this->bob->id => 0,
            $this->carol->id => -10000,
        ], $this->balances());
    }

    public function test_other_groups_expenses_are_ignored(): void
    {
        $other = Group::factory()->create(['created_by' => $this->alice->id]);
        $expense = Expense::factory()->for($other)->create(['paid_by' => $this->alice->id, 'amount_cents' => 5000]);
        $expense->shares()->create(['user_id' => $this->alice->id, 'amount_cents' => 5000]);

        $this->assertSame(0, array_sum(array_map('abs', $this->balances())));
    }
}
