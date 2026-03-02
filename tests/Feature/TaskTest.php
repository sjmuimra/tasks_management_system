<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\TaskManagement\Task;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_own_tasks(): void
    {
        $user = $this->actingAsUser();
        Task::factory(3)->create(['user_id' => $user->id]);
        Task::factory(2)->create();

        $this->getJson('/api/v1/task-management/tasks')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_a_task(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/tasks', [
            'title' => 'My Task',
            'description' => 'Task description',
            'status' => 'todo',
        ])->assertStatus(201)
            ->assertJsonPath('task.title', 'My Task');
    }

    public function test_task_creation_fails_with_invalid_status(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/tasks', [
            'title' => 'My Task',
            'description' => 'Task description',
            'status' => 'invalid_status',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_task_creation_fails_with_past_deadline(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/tasks', [
            'title' => 'My Task',
            'description' => 'Task description',
            'status' => 'todo',
            'deadline' => now()->subDay()->toDateTimeString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_title_cannot_exceed_255_characters(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/tasks', [
            'title' => str_repeat('a', 256),
            'description' => 'desc',
            'status' => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_view_their_own_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(200)
            ->assertJsonPath('id', $task->id);
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(403);
    }

    public function test_user_can_update_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'status' => 'in_progress',
        ])->assertStatus(200)
            ->assertJsonPath('task.title', 'Updated Title');
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'status' => 'done',
        ])->assertStatus(403);
    }

    public function test_regular_user_cannot_edit_overdue_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'status' => 'in_progress',
        ])->assertStatus(403);
    }

    public function test_admin_can_edit_any_overdue_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->overdue()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'done',
        ])->assertStatus(200);
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(200);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->deleteJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(403);
    }

    public function test_regular_user_sees_only_their_overdue_tasks(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->overdue()->create(['user_id' => $user->id]);
        Task::factory()->overdue()->create();

        $this->getJson('/api/v1/task-management/tasks/overdue')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_sees_all_overdue_tasks(): void
    {
        $this->actingAsAdmin();
        Task::factory()->overdue()->count(3)->create();

        $this->getJson('/api/v1/task-management/tasks/overdue')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_done_tasks_are_not_in_overdue_list(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->overdue()->done()->create(['user_id' => $user->id]);

        $this->getJson('/api/v1/task-management/tasks/overdue')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
