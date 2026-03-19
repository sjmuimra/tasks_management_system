<?php

namespace Tests\Feature\Listeners\TaskManagement;

use App\Events\TaskManagement\TaskUpdated;
use App\Models\TaskManagement\Task;
use App\Models\User;
use App\Notifications\TaskManagement\TaskDeadlineOverdue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskUpdateDispatchesEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_task_via_api_dispatches_task_updated_event(): void
    {
        Event::fake([TaskUpdated::class]);

        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => null,
        ]);

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'status' => 'in_progress',
        ])->assertStatus(200);

        Event::assertDispatched(TaskUpdated::class, static function (TaskUpdated $event) use ($task) {
            return $event->task->id === $task->id;
        });
    }

    public function test_event_is_dispatched_exactly_once_per_update(): void
    {
        Event::fake([TaskUpdated::class]);

        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => null,
        ]);

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'status' => 'done',
        ])->assertStatus(200);

        Event::assertDispatchedTimes(TaskUpdated::class, 1);
    }

    public function test_event_is_not_dispatched_on_task_creation(): void
    {
        Event::fake([TaskUpdated::class]);

        $this->actingAsUser();

        $this->postJson('/api/v1/task-management/tasks', [
            'title' => 'New Task',
            'description' => 'Description',
            'status' => 'todo',
        ])->assertStatus(201);

        Event::assertNotDispatched(TaskUpdated::class);
    }

    /**
     * @throws Exception
     */
    public function test_notification_is_sent_when_overdue_task_is_updated(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $this->actingAsAdmin();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'in_progress',
        ])->assertStatus(200);

        Notification::assertSentTo($user, TaskDeadlineOverdue::class);
    }

    public function test_notification_is_not_sent_when_non_overdue_task_is_updated(): void
    {
        Notification::fake();

        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->addDays(5),
            'status' => 'todo',
        ]);

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'status' => 'in_progress',
        ])->assertStatus(200);

        Notification::assertNothingSent();
    }

    public function test_notification_is_not_sent_when_done_overdue_task_is_updated(): void
    {
        Notification::fake();

        $this->actingAsAdmin();
        $task = Task::factory()->overdue()->done()->create();

        $this->putJson("/api/v1/task-management/tasks/$task->id", [
            'title' => $task->title,
            'description' => $task->description,
            'status' => 'done',
        ])->assertStatus(200);

        Notification::assertNothingSent();
    }
}
