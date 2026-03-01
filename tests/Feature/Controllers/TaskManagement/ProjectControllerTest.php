<?php

namespace Tests\Feature\Controllers\TaskManagement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_own_projects(): void
    {
        $user = $this->actingAsUser();
        Project::factory(3)->create(['user_id' => $user->id]);
        Project::factory(2)->create(); // other user's projects

        $this->getJson('/api/v1/task-management/projects')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_project_list_includes_task_count(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory(4)->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $this->getJson('/api/v1/task-management/projects')
            ->assertStatus(200)
            ->assertJsonPath('data.0.tasks_count', 4);
    }

    public function test_index_fails_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/task-management/projects')->assertStatus(401);
    }

    public function test_user_can_create_a_project(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/projects', [
            'name'        => 'My Project',
            'description' => 'Project description',
        ])->assertStatus(201)
            ->assertJsonPath('message', 'Project created successfully.')
            ->assertJsonPath('project.name', 'My Project');
    }

    public function test_project_is_assigned_to_authenticated_user(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/v1/task-management/projects', [
            'name' => 'My Project',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name'    => 'My Project',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_fails_when_name_is_missing(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/projects', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_view_a_project_with_tasks(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory(3)->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $this->getJson("/api/v1/task-management/projects/{$project->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $project->id)
            ->assertJsonCount(3, 'tasks');
    }

    public function test_user_can_update_their_project(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/{$project->id}", [
            'name' => 'Updated Name',
        ])->assertStatus(200)
            ->assertJsonPath('message', 'Project updated successfully.')
            ->assertJsonPath('project.name', 'Updated Name');
    }

    public function test_update_fails_when_name_is_empty(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/{$project->id}", [
            'name' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_delete_their_project(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/task-management/projects/{$project->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Project deleted successfully.');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_tasks_become_projectless_when_project_is_deleted(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task    = Task::factory()->create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
        ]);

        $this->deleteJson("/api/v1/task-management/projects/{$project->id}")->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id'         => $task->id,
            'project_id' => null,
        ]);
    }
}
