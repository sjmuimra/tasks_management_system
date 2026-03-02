<?php

namespace Tests\Unit\Notifications\TaskManagement;

use App\Notifications\TaskManagement\TaskDeadlineOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Tests\TestCase;

class TaskDeadlineOverdueTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotification(?Task $task = null): TaskDeadlineOverdue
    {
        $task ??= Task::factory()->overdue()->create();

        return new TaskDeadlineOverdue($task);
    }

    public function test_notification_is_sent_via_mail(): void
    {
        $notification = $this->makeNotification();
        $user = User::factory()->create();

        $this->assertEquals(['mail'], $notification->via($user));
    }

    public function test_to_mail_returns_mail_message_instance(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification();

        $this->assertInstanceOf(MailMessage::class, $notification->toMail($user));
    }

    public function test_mail_subject_contains_task_title(): void
    {
        $task = Task::factory()->overdue()->create([
            'title' => 'Fix the bug',
        ]);
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('Fix the bug', $mail->subject);
    }

    public function test_mail_greeting_contains_user_name(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $task = Task::factory()->overdue()->create(['user_id' => $user->id]);

        $notification = new TaskDeadlineOverdue($task);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('John Doe', $mail->greeting);
    }

    public function test_mail_contains_task_title_in_body(): void
    {
        $task = Task::factory()->overdue()->create([
            'title' => 'Fix the bug',
        ]);
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);
        $mail = $notification->toMail($user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('Fix the bug', $introLines);
    }

    public function test_mail_contains_task_status_in_body(): void
    {
        $task = Task::factory()->overdue()->create([
            'status' => 'in_progress',
        ]);
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);
        $mail = $notification->toMail($user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('in_progress', $introLines);
    }

    public function test_mail_action_points_to_task_url(): void
    {
        $task = Task::factory()->overdue()->create();
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);
        $mail = $notification->toMail($user);

        $this->assertStringContainsString(
            "/api/tasks/$task->id",
            $mail->actionUrl
        );
    }

    public function test_to_array_contains_correct_keys(): void
    {
        $task = Task::factory()->overdue()->create();
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);

        $array = $notification->toArray($user);

        $this->assertArrayHasKey('task_id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('deadline', $array);
        $this->assertArrayHasKey('status', $array);
    }

    public function test_to_array_contains_correct_values(): void
    {
        $task = Task::factory()->overdue()->create();
        $user = User::factory()->create();
        $notification = new TaskDeadlineOverdue($task);

        $array = $notification->toArray($user);

        $this->assertEquals($task->id, $array['task_id']);
        $this->assertEquals($task->title, $array['title']);
        $this->assertEquals($task->status, $array['status']);
    }

    public function test_notification_implements_should_queue(): void
    {
        $this->assertInstanceOf(
            ShouldQueue::class,
            $this->makeNotification()
        );
    }
}
