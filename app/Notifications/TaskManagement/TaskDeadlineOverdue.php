<?php

namespace App\Notifications\TaskManagement;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TaskManagement\Task;
use Illuminate\Bus\Queueable;

class TaskDeadlineOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task Overdue: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your task **\"{$this->task->title}\"** has passed its deadline.")
            ->line("Deadline was: **{$this->task->deadline->toFormattedDateString()}**")
            ->line("Current status: **{$this->task->status}**")
            ->action('View Task', url("/api/tasks/{$this->task->id}"))
            ->line('Please update the task status or contact your project manager.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id'  => $this->task->id,
            'title'    => $this->task->title,
            'deadline' => $this->task->deadline,
            'status'   => $this->task->status,
        ];
    }
}
