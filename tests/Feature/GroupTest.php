<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_group_and_becomes_member(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Baguio Trip',
            'currency' => 'php',
        ]);

        $group = Group::firstOrFail();
        $response->assertRedirect(route('groups.show', $group));
        $this->assertSame('Baguio Trip', $group->name);
        $this->assertSame('PHP', $group->currency);
        $this->assertSame($user->id, $group->created_by);
        $this->assertTrue($group->hasMember($user));
    }

    public function test_group_name_is_required_and_currency_is_three_letters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('groups.store'), [
            'name' => '',
            'currency' => 'PESO',
        ])->assertSessionHasErrors(['name', 'currency']);

        $this->assertSame(0, Group::count());
    }

    public function test_index_lists_only_my_groups(): void
    {
        $user = User::factory()->create();
        $mine = Group::factory()->create(['created_by' => $user->id]);
        Group::factory()->create();

        $this->actingAs($user)->get(route('groups.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Index')
                ->has('groups', 1)
                ->where('groups.0.name', $mine->name));
    }

    public function test_show_passes_group_and_members(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);
        $group->members()->attach(User::factory()->create());

        $this->actingAs($user)->get(route('groups.show', $group))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Groups/Show')
                ->where('group.id', $group->id)
                ->has('members', 2));
    }

    public function test_group_page_passes_balances_and_suggestions(): void
    {
        [$alice, $bob, $carol] = User::factory()->count(3)->create()->all();
        $group = Group::factory()->create(['created_by' => $alice->id]);
        $group->members()->attach([$bob->id, $carol->id]);
        $expense = Expense::factory()->for($group)->create(['paid_by' => $alice->id, 'amount_cents' => 30000]);
        foreach ([$alice, $bob, $carol] as $member) {
            $expense->shares()->create(['user_id' => $member->id, 'amount_cents' => 10000]);
        }

        $this->actingAs($bob)->get(route('groups.show', $group))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('balances.0.user_id', $alice->id)
                ->where('balances.0.name', $alice->name)
                ->where('balances.0.cents', 20000)
                ->where('balances.0.display', 'PHP 200.00')
                ->where('balances.1.cents', -10000)
                ->has('suggestions', 2)
                ->where('suggestions.0.from_id', $bob->id)
                ->where('suggestions.0.from_name', $bob->name)
                ->where('suggestions.0.to_id', $alice->id)
                ->where('suggestions.0.to_name', $alice->name)
                ->where('suggestions.0.cents', 10000)
                ->where('suggestions.0.display', 'PHP 100.00')
                ->where('suggestions.0.amount', '100.00'));
    }

    public function test_non_member_cannot_view_group(): void
    {
        $group = Group::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('groups.show', $group))
            ->assertForbidden();
    }

    public function test_member_can_add_registered_user_by_email(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('groups.members.store', $group), ['email' => $friend->email])
            ->assertRedirect(route('groups.show', $group));

        $this->assertTrue($group->hasMember($friend));
    }

    public function test_adding_unknown_email_fails(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('groups.members.store', $group), ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_adding_existing_member_fails(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('groups.members.store', $group), ['email' => $user->email])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, $group->members()->count());
    }

    public function test_member_with_zero_balance_can_be_removed(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);
        $group->members()->attach($friend);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, $friend]))
            ->assertRedirect(route('groups.show', $group));

        $this->assertFalse($group->hasMember($friend));
    }

    public function test_member_with_nonzero_balance_cannot_be_removed(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);
        $group->members()->attach($friend);
        $expense = Expense::factory()->for($group)->create(['paid_by' => $user->id, 'amount_cents' => 2000]);
        $expense->shares()->create(['user_id' => $user->id, 'amount_cents' => 1000]);
        $expense->shares()->create(['user_id' => $friend->id, 'amount_cents' => 1000]);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, $friend]))
            ->assertSessionHasErrors('member');

        $this->assertTrue($group->hasMember($friend));
    }

    public function test_member_with_expense_history_cannot_be_removed_even_when_settled(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);
        $group->members()->attach($friend);
        $expense = Expense::factory()->for($group)->create(['paid_by' => $user->id, 'amount_cents' => 2000]);
        $expense->shares()->create(['user_id' => $user->id, 'amount_cents' => 1000]);
        $expense->shares()->create(['user_id' => $friend->id, 'amount_cents' => 1000]);
        $group->settlements()->create([
            'from_user_id' => $friend->id,
            'to_user_id' => $user->id,
            'amount_cents' => 1000,
            'settled_on' => '2026-10-29',
            'created_by' => $friend->id,
        ]);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, $friend]))
            ->assertSessionHasErrors('member');

        $this->assertTrue($group->hasMember($friend));
    }

    public function test_member_who_only_logged_an_expense_cannot_be_removed(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);
        $group->members()->attach($friend);
        $expense = Expense::factory()->for($group)->create([
            'paid_by' => $user->id,
            'created_by' => $friend->id,
            'amount_cents' => 1000,
        ]);
        $expense->shares()->create(['user_id' => $user->id, 'amount_cents' => 1000]);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, $friend]))
            ->assertSessionHasErrors('member');
    }

    public function test_last_member_cannot_be_removed(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, $user]))
            ->assertSessionHasErrors('member');

        $this->assertTrue($group->hasMember($user));
    }

    public function test_non_member_cannot_remove_members(): void
    {
        $group = Group::factory()->create();
        $creator = User::findOrFail($group->created_by);
        $group->members()->attach(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete(route('groups.members.destroy', [$group, $creator]))
            ->assertForbidden();

        $this->assertTrue($group->hasMember($creator));
    }

    public function test_removing_someone_not_in_group_returns_404(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('groups.members.destroy', [$group, User::factory()->create()]))
            ->assertNotFound();
    }

    public function test_non_member_cannot_add_members(): void
    {
        $group = Group::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->post(route('groups.members.store', $group), ['email' => $outsider->email])
            ->assertForbidden();

        $this->assertFalse($group->hasMember($outsider));
    }
}
