<?php

namespace Database\Factories\TaskManagement;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(['todo', 'in_progress', 'done']),
            'deadline'    => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'user_id'     => User::factory(),
            'project_id'  => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'status'   => fake()->randomElement(['todo', 'in_progress']),
        ]);
    }

    public function todo(): static
    {
        return $this->state(['status' => 'todo']);
    }

    public function done(): static
    {
        return $this->state(['status' => 'done']);
    }
}
