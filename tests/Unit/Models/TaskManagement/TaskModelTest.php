<?php

namespace Tests\Unit\Models\TaskManagement;

use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes_are_correct(): void
    {
        $task = new Task;

        $this->assertEquals(
            ['title', 'description', 'status', 'deadline', 'user_id', 'project_id'],
            $task->getFillable()
        );
    }

    public function test_deadline_is_cast_to_datetime(): void
    {
        $task = Task::factory()->create([
            'deadline' => now()->addYear()->toDateTimeString(),
        ]);

        $this->assertInstanceOf(Carbon::class, $task->deadline);
    }

    public function test_deadline_can_be_null(): void
    {
        $task = Task::factory()->create(['deadline' => null]);

        $this->assertNull($task->deadline);
    }

    public function test_task_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->id, $task->user->id);
    }

    public function test_task_belongs_to_a_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    public function test_task_project_is_null_when_not_assigned(): void
    {
        $task = Task::factory()->create(['project_id' => null]);

        $this->assertNull($task->project);
    }

    public function test_deleting_user_deletes_their_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('tasks', ['user_id' => $user->id]);
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

    public function test_overdue_scope_returns_tasks_with_past_deadline(): void
    {
        $overdueTask = Task::factory()->overdue()->create();
        $upcomingTask = Task::factory()->create([
            'deadline' => now()->addDays(5),
            'status' => 'todo',
        ]);

        $results = Task::overdue()->get();

        $this->assertTrue($results->contains($overdueTask));
        $this->assertFalse($results->contains($upcomingTask));
    }

    public function test_overdue_scope_excludes_done_tasks(): void
    {
        $donTask = Task::factory()->overdue()->done()->create();

        $results = Task::overdue()->get();

        $this->assertFalse($results->contains($donTask));
    }

    public function test_overdue_scope_excludes_tasks_without_deadline(): void
    {
        $taskWithoutDeadline = Task::factory()->create(['deadline' => null]);

        $results = Task::overdue()->get();

        $this->assertFalse($results->contains($taskWithoutDeadline));
    }

    public function test_for_user_scope_returns_only_given_users_tasks(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userTask = Task::factory()->create(['user_id' => $user->id]);
        $otherTask = Task::factory()->create(['user_id' => $otherUser->id]);

        $results = Task::forUser($user->id)->get();

        $this->assertTrue($results->contains($userTask));
        $this->assertFalse($results->contains($otherTask));
    }

    public function test_for_project_scope_returns_only_tasks_in_given_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $otherProject = Project::factory()->create(['user_id' => $user->id]);

        $projectTask = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
        $otherTask = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $otherProject->id,
        ]);

        $results = Task::forProject($project->id)->get();

        $this->assertTrue($results->contains($projectTask));
        $this->assertFalse($results->contains($otherTask));
    }

    public function test_overdue_factory_state_creates_overdue_task(): void
    {
        $task = Task::factory()->overdue()->create();

        $this->assertTrue($task->deadline->isPast());
        $this->assertNotEquals('done', $task->status);
    }

    public function test_done_factory_state_creates_done_task(): void
    {
        $task = Task::factory()->done()->create();

        $this->assertEquals('done', $task->status);
    }

    public function test_todo_factory_state_creates_todo_task(): void
    {
        $task = Task::factory()->todo()->create();

        $this->assertEquals('todo', $task->status);
    }

    public function test_task_is_persisted_to_database(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'title' => 'Test Task',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Test Task',
        ]);
    }
}
