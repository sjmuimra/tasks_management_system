<?php

namespace Tests\Unit\Listeners\TaskManagement;

use App\Listeners\TaskManagement\SendOverdueTaskNotification;
use App\Notifications\TaskManagement\TaskDeadlineOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\TaskManagement\TaskUpdated;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Tests\TestCase;
use Exception;
use Mockery;
use Log;

class SendOverdueTaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeListener(): SendOverdueTaskNotification
    {
        return new SendOverdueTaskNotification();
    }

    public function test_should_queue_returns_true_for_overdue_task(): void
    {
        $task = Task::factory()->overdue()->create();
        $event = new TaskUpdated($task);
        $listener = $this->makeListener();

        $this->assertTrue($listener->shouldQueue($event));
    }

    public function test_should_queue_returns_false_when_deadline_is_null(): void
    {
        $task = Task::factory()->create(['deadline' => null]);
        $event = new TaskUpdated($task);
        $listener = $this->makeListener();

        $this->assertFalse($listener->shouldQueue($event));
    }

    public function test_should_queue_returns_false_when_deadline_is_in_future(): void
    {
        $task = Task::factory()->create([
            'deadline' => now()->addDays(5),
            'status'   => 'todo',
        ]);
        $event = new TaskUpdated($task);
        $listener = $this->makeListener();

        $this->assertFalse($listener->shouldQueue($event));
    }

    public function test_should_queue_returns_false_when_task_is_done(): void
    {
        $task = Task::factory()->create([
            'deadline' => now()->subDays(3),
            'status'   => 'done',
        ]);
        $event = new TaskUpdated($task);
        $listener = $this->makeListener();

        $this->assertFalse($listener->shouldQueue($event));
    }

    public function test_should_queue_returns_true_when_in_progress_and_overdue(): void
    {
        $task = Task::factory()->create([
            'deadline' => now()->subDays(1),
            'status'   => 'in_progress',
        ]);
        $event = new TaskUpdated($task);
        $listener = $this->makeListener();

        $this->assertTrue($listener->shouldQueue($event));
    }

    /**
     * @throws Exception
     */
    public function test_handle_sends_notification_to_task_owner(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $listener = $this->makeListener();
        $listener->handle(new TaskUpdated($task));

        Notification::assertSentTo($user, TaskDeadlineOverdue::class);
    }

    /**
     * @throws Exception
     */
    public function test_handle_sends_correct_notification_class(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $listener = $this->makeListener();
        $listener->handle(new TaskUpdated($task));

        Notification::assertSentTo(
            $user,
            TaskDeadlineOverdue::class,
            static function (TaskDeadlineOverdue $notification) use ($task) {
                return $notification->task->id === $task->id;
            }
        );
    }

    public function test_handle_sends_exactly_one_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $listener = $this->makeListener();
        $listener->handle(new TaskUpdated($task));

        Notification::assertSentToTimes($user, TaskDeadlineOverdue::class, 1);
    }

    public function test_failed_logs_error_with_task_id_and_message(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('SendOverdueTaskNotification failed', Mockery::on(static function (array $context) {
                return isset($context['task_id'], $context['error']);
            }));

        $task = Task::factory()->create();
        $event = new TaskUpdated($task);
        $exception = new Exception('Something went wrong');
        $listener = $this->makeListener();

        $listener->failed($event, $exception);
    }

    public function test_listener_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            $this->makeListener()
        );
    }
}
