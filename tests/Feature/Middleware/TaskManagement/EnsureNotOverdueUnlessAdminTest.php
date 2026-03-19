<?php

namespace Tests\Feature\Middleware\TaskManagement;

use App\Models\TaskManagement\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureNotOverdueUnlessAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_their_own_overdue_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'status' => 'in_progress',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Only admins can edit tasks with an overdue deadline.');
    }

    public function test_user_can_edit_their_own_non_overdue_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->addDays(5),
            'status' => 'todo',
        ]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'in_progress',
        ])->assertStatus(200);
    }

    public function test_user_can_edit_task_without_deadline(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => null,
            'status' => 'todo',
        ]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'in_progress',
        ])->assertStatus(200);
    }

    public function test_user_can_edit_overdue_task_that_is_already_done(): void
    {
        $user = $this->actingAsUser();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->subDays(3),
            'status' => 'done',
        ]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'done',
        ])->assertStatus(200);
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

    public function test_admin_can_edit_their_own_overdue_task(): void
    {
        $admin = $this->actingAsAdmin();
        $task = Task::factory()->overdue()->create(['user_id' => $admin->id]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'in_progress',
        ])->assertStatus(200);
    }

    public function test_unauthenticated_request_is_rejected_before_overdue_check(): void
    {
        $task = Task::factory()->overdue()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'status' => 'done',
        ])->assertStatus(401);
    }
}
