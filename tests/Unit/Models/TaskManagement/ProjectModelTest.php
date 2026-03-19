<?php

namespace Tests\Unit\Models\TaskManagement;

use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes_are_correct(): void
    {
        $project = new Project;

        $this->assertEquals(
            ['name', 'description', 'user_id'],
            $project->getFillable()
        );
    }

    public function test_project_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    public function test_project_has_many_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Task::factory(4)->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->assertCount(4, $project->tasks);
        $this->assertInstanceOf(Task::class, $project->tasks->first());
    }

    public function test_project_tasks_are_empty_when_no_tasks_assigned(): void
    {
        $project = Project::factory()->create();

        $this->assertCount(0, $project->tasks);
    }

    public function test_deleting_project_sets_task_project_id_to_null(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $project->delete();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'project_id' => null,
        ]);
    }

    public function test_deleting_user_cascades_to_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_project_is_persisted_to_database(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'name' => 'Test Project',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Test Project',
        ]);
    }

    public function test_description_is_optional(): void
    {
        $project = Project::factory()->create(['description' => null]);

        $this->assertNull($project->description);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'description' => null,
        ]);
    }
}
