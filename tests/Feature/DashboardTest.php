<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_my_groups_with_my_balance(): void
    {
        [$alice, $bob, $carol] = User::factory()->count(3)->create()->all();
        $group = Group::factory()->create(['name' => 'Baguio Trip', 'created_by' => $alice->id]);
        $group->members()->attach([$bob->id, $carol->id]);
        $expense = Expense::factory()->for($group)->create(['paid_by' => $alice->id, 'amount_cents' => 30000]);
        foreach ([$alice, $bob, $carol] as $member) {
            $expense->shares()->create(['user_id' => $member->id, 'amount_cents' => 10000]);
        }
        Group::factory()->create(['name' => 'Not mine']);

        $this->actingAs($alice)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('groups', 1)
                ->where('groups.0.id', $group->id)
                ->where('groups.0.name', 'Baguio Trip')
                ->where('groups.0.currency', 'PHP')
                ->where('groups.0.my_balance_cents', 20000)
                ->where('groups.0.my_balance_display', 'PHP 200.00'));

        $this->actingAs($bob)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.0.my_balance_cents', -10000)
                ->where('groups.0.my_balance_display', 'PHP 100.00'));
    }

    public function test_dashboard_with_no_groups_passes_empty_list(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('groups', 0));
    }

    public function test_home_redirects_signed_in_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_home_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
