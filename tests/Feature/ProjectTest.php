<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_project(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/projects', [
            'name' => 'My Project',
            'description' => 'Project description',
        ])->assertStatus(201)
            ->assertJsonPath('project.name', 'My Project');
    }

    public function test_user_can_list_their_projects(): void
    {
        $user = $this->actingAsUser();
        Project::factory(2)->create(['user_id' => $user->id]);
        Project::factory(3)->create();

        $this->getJson('/api/v1/task-management/projects')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_project_with_tasks(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory(3)->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $this->getJson("/api/v1/task-management/projects/$project->id")
            ->assertStatus(200)
            ->assertJsonCount(3, 'tasks');
    }

    public function test_user_can_update_their_project(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => 'Updated Name',
        ])->assertStatus(200)
            ->assertJsonPath('project.name', 'Updated Name');
    }

    public function test_user_can_delete_their_project(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/task-management/projects/$project->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_tasks_become_project_less_when_project_deleted(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $this->deleteJson("/api/v1/task-management/projects/$project->id")->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'project_id' => null,
        ]);
    }
}
