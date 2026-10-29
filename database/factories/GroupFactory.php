<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'currency' => 'PHP',
            'created_by' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Group $group) => $group->members()->attach($group->created_by));
    }
}
