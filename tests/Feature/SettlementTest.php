<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->alice, $this->bob] = User::factory()->count(2)->create()->all();
        $this->group = Group::factory()->create(['created_by' => $this->alice->id]);
        $this->group->members()->attach($this->bob);
    }

    private function storeAs(User $user, array $overrides = [])
    {
        return $this->actingAs($user)->post(route('groups.settlements.store', $this->group), array_merge([
            'from_user_id' => $this->bob->id,
            'to_user_id' => $this->alice->id,
            'amount' => '100',
            'settled_on' => '2026-10-29',
        ], $overrides));
    }

    public function test_member_records_settlement(): void
    {
        $this->storeAs($this->bob)->assertRedirect(route('groups.show', $this->group));

        $settlement = Settlement::firstOrFail();
        $this->assertSame($this->group->id, $settlement->group_id);
        $this->assertSame($this->bob->id, $settlement->from_user_id);
        $this->assertSame($this->alice->id, $settlement->to_user_id);
        $this->assertSame(10000, $settlement->amount_cents);
        $this->assertSame($this->bob->id, $settlement->created_by);
    }

    public function test_from_and_to_must_differ(): void
    {
        $this->storeAs($this->bob, ['to_user_id' => $this->bob->id])
            ->assertSessionHasErrors('to_user_id');

        $this->assertSame(0, Settlement::count());
    }

    public function test_amount_must_be_valid(): void
    {
        $this->storeAs($this->bob, ['amount' => '0'])->assertSessionHasErrors('amount');
        $this->storeAs($this->bob, ['amount' => '1.234'])->assertSessionHasErrors('amount');
    }

    public function test_both_parties_must_be_members(): void
    {
        $outsider = User::factory()->create();

        $this->storeAs($this->bob, ['from_user_id' => $outsider->id])->assertSessionHasErrors('from_user_id');
        $this->storeAs($this->bob, ['to_user_id' => $outsider->id])->assertSessionHasErrors('to_user_id');

        $this->assertSame(0, Settlement::count());
    }

    public function test_non_member_cannot_record_settlement(): void
    {
        $this->storeAs(User::factory()->create())->assertForbidden();
    }

    public function test_settlement_can_be_deleted(): void
    {
        $this->storeAs($this->bob);
        $settlement = Settlement::firstOrFail();

        $this->actingAs($this->alice)
            ->delete(route('groups.settlements.destroy', [$this->group, $settlement]))
            ->assertRedirect(route('groups.show', $this->group));

        $this->assertSame(0, Settlement::count());
    }

    public function test_non_member_cannot_delete_settlement(): void
    {
        $this->storeAs($this->bob);

        $this->actingAs(User::factory()->create())
            ->delete(route('groups.settlements.destroy', [$this->group, Settlement::firstOrFail()]))
            ->assertForbidden();

        $this->assertSame(1, Settlement::count());
    }

    public function test_settlement_from_other_group_returns_404(): void
    {
        $other = Group::factory()->create();
        $theirs = $other->settlements()->create([
            'from_user_id' => $other->created_by,
            'to_user_id' => $other->created_by,
            'amount_cents' => 100,
            'settled_on' => '2026-10-29',
            'created_by' => $other->created_by,
        ]);

        $this->actingAs($this->alice)
            ->delete(route('groups.settlements.destroy', [$this->group, $theirs]))
            ->assertNotFound();
    }

    public function test_group_page_lists_settlements(): void
    {
        $this->storeAs($this->bob);

        $this->actingAs($this->alice)->get(route('groups.show', $this->group))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('settlements', 1)
                ->where('settlements.0.from_name', $this->bob->name)
                ->where('settlements.0.to_name', $this->alice->name)
                ->where('settlements.0.settled_on', '2026-10-29')
                ->where('settlements.0.amount_cents', 10000)
                ->where('settlements.0.amount_display', 'PHP 100.00'));
    }
}
