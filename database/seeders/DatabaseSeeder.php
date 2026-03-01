<?php

namespace Database\Seeders;

use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $user = User::factory()->create([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $adminProjects = Project::factory(3)->create(['user_id' => $admin->id]);

        $userProjects = Project::factory(2)->create(['user_id' => $user->id]);

        foreach ($adminProjects as $project) {
            Task::factory(5)->create([
                'user_id'    => $admin->id,
                'project_id' => $project->id,
            ]);
            Task::factory(2)->overdue()->create([
                'user_id'    => $admin->id,
                'project_id' => $project->id,
            ]);
        }

        foreach ($userProjects as $project) {
            Task::factory(4)->create([
                'user_id'    => $user->id,
                'project_id' => $project->id,
            ]);
        }

        User::factory(3)->create()->each(function (User $u) {
            $project = Project::factory()->create(['user_id' => $u->id]);

            Task::factory(3)->create([
                'user_id'    => $u->id,
                'project_id' => $project->id,
            ]);

            Task::factory(1)->overdue()->create([
                'user_id' => $u->id,
            ]);
        });
    }
}
