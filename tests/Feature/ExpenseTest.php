<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseShare;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExpenseTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'description' => 'Dinner',
            'amount' => '100',
            'spent_on' => '2026-10-28',
            'paid_by' => $this->alice->id,
            'split_type' => 'equal',
            'participants' => [$this->alice->id, $this->bob->id, $this->carol->id],
            'shares' => [],
            'percents' => [],
        ], $overrides);
    }

    private function storeAs(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post(
            route('groups.expenses.store', $this->group),
            $this->payload($overrides),
        );
    }

    private function sharesOf(Expense $expense): array
    {
        return $expense->shares()->orderBy('user_id')->pluck('amount_cents', 'user_id')->all();
    }

    public function test_member_adds_equal_expense_and_shares_are_saved(): void
    {
        $this->storeAs($this->alice)->assertRedirect(route('groups.show', $this->group));

        $expense = Expense::firstOrFail();
        $this->assertSame(10000, $expense->amount_cents);
        $this->assertSame($this->alice->id, $expense->paid_by);
        $this->assertSame([
            $this->alice->id => 3334,
            $this->bob->id => 3333,
            $this->carol->id => 3333,
        ], $this->sharesOf($expense));
    }

    public function test_exact_split_saved(): void
    {
        $this->storeAs($this->alice, [
            'split_type' => 'exact',
            'shares' => [$this->alice->id => '70', $this->bob->id => '30.00'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->alice->id => 7000, $this->bob->id => 3000],
            $this->sharesOf(Expense::firstOrFail()),
        );
    }

    public function test_percent_split_saved(): void
    {
        $this->storeAs($this->alice, [
            'amount' => '200',
            'split_type' => 'percent',
            'percents' => [$this->alice->id => '50', $this->bob->id => '30', $this->carol->id => '20'],
        ])->assertSessionHasNoErrors();

        $this->assertSame([
            $this->alice->id => 10000,
            $this->bob->id => 6000,
            $this->carol->id => 4000,
        ], $this->sharesOf(Expense::firstOrFail()));
    }

    public function test_blank_rows_in_exact_split_are_ignored(): void
    {
        $this->storeAs($this->alice, [
            'split_type' => 'exact',
            'shares' => [$this->alice->id => '100', $this->bob->id => '', $this->carol->id => null],
        ])->assertSessionHasNoErrors();

        $this->assertSame([$this->alice->id => 10000], $this->sharesOf(Expense::firstOrFail()));
    }

    public function test_exact_shares_not_matching_total_is_rejected(): void
    {
        $this->storeAs($this->alice, [
            'split_type' => 'exact',
            'shares' => [$this->alice->id => '70', $this->bob->id => '20'],
        ])->assertSessionHasErrors('shares');

        $this->assertSame(0, Expense::count());
    }

    public function test_percents_not_summing_to_100_is_rejected(): void
    {
        $this->storeAs($this->alice, [
            'split_type' => 'percent',
            'percents' => [$this->alice->id => '60', $this->bob->id => '30'],
        ])->assertSessionHasErrors('percents');

        $this->assertSame(0, Expense::count());
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $this->storeAs($this->alice, ['amount' => '12.345'])->assertSessionHasErrors('amount');
        $this->storeAs($this->alice, ['amount' => '0'])->assertSessionHasErrors('amount');
        $this->storeAs($this->alice, ['amount' => '-5'])->assertSessionHasErrors('amount');

        $this->assertSame(0, Expense::count());
    }

    public function test_invalid_share_amount_is_rejected(): void
    {
        $this->storeAs($this->alice, [
            'split_type' => 'exact',
            'shares' => [$this->alice->id => 'abc', $this->bob->id => '100'],
        ])->assertSessionHasErrors('shares');
    }

    public function test_payer_must_be_group_member(): void
    {
        $outsider = User::factory()->create();

        $this->storeAs($this->alice, ['paid_by' => $outsider->id])->assertSessionHasErrors('paid_by');
    }

    public function test_participant_must_be_group_member(): void
    {
        $outsider = User::factory()->create();

        $this->storeAs($this->alice, ['participants' => [$outsider->id]])
            ->assertSessionHasErrors('participants.0');
    }

    public function test_share_user_must_be_group_member(): void
    {
        $outsider = User::factory()->create();

        $this->storeAs($this->alice, [
            'split_type' => 'exact',
            'shares' => [$outsider->id => '100'],
        ])->assertSessionHasErrors('shares');

        $this->assertSame(0, Expense::count());
    }

    public function test_non_member_cannot_create_expense(): void
    {
        $this->storeAs(User::factory()->create())->assertForbidden();

        $this->assertSame(0, Expense::count());
    }

    public function test_non_member_cannot_open_create_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('groups.expenses.create', $this->group))
            ->assertForbidden();
    }

    public function test_editing_expense_replaces_shares(): void
    {
        $this->storeAs($this->alice);
        $expense = Expense::firstOrFail();

        $this->actingAs($this->bob)
            ->put(route('groups.expenses.update', [$this->group, $expense]), $this->payload([
                'amount' => '50',
                'paid_by' => $this->bob->id,
                'participants' => [$this->bob->id, $this->carol->id],
            ]))
            ->assertRedirect(route('groups.show', $this->group));

        $expense->refresh();
        $this->assertSame(5000, $expense->amount_cents);
        $this->assertSame($this->bob->id, $expense->paid_by);
        $this->assertSame([$this->bob->id => 2500, $this->carol->id => 2500], $this->sharesOf($expense));
    }

    public function test_deleting_expense_removes_shares(): void
    {
        $this->storeAs($this->alice);
        $expense = Expense::firstOrFail();

        $this->actingAs($this->carol)
            ->delete(route('groups.expenses.destroy', [$this->group, $expense]))
            ->assertRedirect(route('groups.show', $this->group));

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, ExpenseShare::count());
    }

    public function test_expense_from_other_group_returns_404(): void
    {
        $other = Group::factory()->create();
        $theirs = Expense::factory()->for($other)->create(['paid_by' => $other->created_by]);

        $this->actingAs($this->alice)
            ->get(route('groups.expenses.edit', [$this->group, $theirs]))
            ->assertNotFound();
    }

    public function test_edit_page_prefills_expense(): void
    {
        $this->storeAs($this->alice);
        $expense = Expense::firstOrFail();

        $this->actingAs($this->alice)
            ->get(route('groups.expenses.edit', [$this->group, $expense]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Expenses/Edit')
                ->where('expense.amount', '100.00')
                ->where('expense.split_type', 'equal')
                ->where('expense.paid_by', $this->alice->id)
                ->has('expense.participants', 3)
                ->has('members', 3));
    }

    public function test_group_page_lists_expenses(): void
    {
        $this->storeAs($this->alice);

        $this->actingAs($this->bob)
            ->get(route('groups.show', $this->group))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('expenses', 1)
                ->where('expenses.0.description', 'Dinner')
                ->where('expenses.0.payer_name', $this->alice->name)
                ->where('expenses.0.amount_cents', 10000)
                ->where('expenses.0.amount_display', 'PHP 100.00'));
    }
}
