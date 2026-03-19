<?php

namespace Tests\Feature\Middleware\TaskManagement;

use App\Models\TaskManagement\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureTaskOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_own_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(200);
    }

    public function test_user_cannot_view_task_belonging_to_another_user(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You do not have permission to access this task.');
    }

    public function test_user_cannot_update_task_belonging_to_another_user(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => 'Hacked Title',
            'description' => 'Hacked',
            'status' => 'done',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'You do not have permission to access this task.');
    }

    public function test_user_cannot_delete_task_belonging_to_another_user(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->deleteJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You do not have permission to access this task.');
    }

    public function test_admin_can_view_any_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(200);
    }

    public function test_admin_can_update_any_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => 'Admin Updated',
            'description' => $task->description,
            'status' => 'done',
        ])->assertStatus(200);
    }

    public function test_admin_can_delete_any_task(): void
    {
        $this->actingAsAdmin();
        $task = Task::factory()->create();

        $this->deleteJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(200);
    }

    public function test_unauthenticated_request_is_rejected_before_ownership_check(): void
    {
        $task = Task::factory()->create();

        $this->getJson("/api/v1/task-management/tasks/$task->id")
            ->assertStatus(401);
    }
}
