<?php

namespace Database\Factories\TaskManagement;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->words(3, true),
            'description' => fake()->sentence(),
            'user_id'     => User::factory(),
        ];
    }
}
