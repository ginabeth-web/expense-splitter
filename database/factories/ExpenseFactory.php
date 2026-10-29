<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'paid_by' => fn (array $attributes) => Group::find($attributes['group_id'])->created_by,
            'created_by' => fn (array $attributes) => $attributes['paid_by'],
            'description' => fake()->words(3, true),
            'amount_cents' => 10000,
            'split_type' => 'equal',
            'spent_on' => now()->toDateString(),
        ];
    }
}
