<?php

namespace Tests\Feature;

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
