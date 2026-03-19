<?php

namespace App\Listeners\TaskManagement;

use App\Events\TaskManagement\TaskUpdated;
use App\Models\User;
use App\Notifications\TaskManagement\TaskDeadlineOverdue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Log;

class SendOverdueTaskNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function shouldQueue(TaskUpdated $event): bool
    {
        $task = $event->task;

        return $task->deadline !== null
            && $task->deadline->isPast()
            && $task->status !== 'done';
    }

    public function handle(TaskUpdated $event): void
    {
        $task = $event->task->loadMissing('user');

        $user = $task->user;

        if (! $user instanceof User) {
            return;
        }

        $user->notify(new TaskDeadlineOverdue($task));
    }

    public function failed(TaskUpdated $event, \Throwable $exception): void
    {
        Log::error('SendOverdueTaskNotification failed', [
            'task_id' => $event->task->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
