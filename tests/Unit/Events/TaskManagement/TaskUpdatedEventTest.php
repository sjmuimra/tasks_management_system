<?php

namespace Tests\Unit\Events\TaskManagement;

use App\Events\TaskManagement\TaskUpdated;
use App\Models\TaskManagement\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\SerializesModels;
use Tests\TestCase;

class TaskUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_holds_the_task_instance(): void
    {
        $task = Task::factory()->create();
        $event = new TaskUpdated($task);

        $this->assertInstanceOf(Task::class, $event->task);
        $this->assertEquals($task->id, $event->task->id);
    }

    public function test_task_property_is_readonly(): void
    {
        $task = Task::factory()->create();
        $event = new TaskUpdated($task);

        $this->expectException(\Error::class);

        $event->task = Task::factory()->create();
    }

    public function test_event_uses_dispatchable_trait(): void
    {
        $this->assertContains(
            Dispatchable::class,
            class_uses_recursive(TaskUpdated::class)
        );
    }

    public function test_event_uses_serializes_models_trait(): void
    {
        $this->assertContains(
            SerializesModels::class,
            class_uses_recursive(TaskUpdated::class)
        );
    }
}
