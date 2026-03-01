<?php

namespace Tests\Feature\Requests\TaskManagement\Task;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use Tests\TestCase;

class UpdateTaskRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(): Task
    {
        $user = $this->actingAsUser();

        return Task::factory()->create([
            'user_id'  => $user->id,
            'deadline' => null,
            'status'   => 'todo',
        ]);
    }

    public function test_title_is_optional_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'description' => 'Updated desc',
            'status'      => 'todo',
        ])->assertStatus(200);
    }

    public function test_title_cannot_be_empty_string_when_provided(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'title' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_cannot_exceed_255_characters_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'title' => str_repeat('a', 256),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_description_is_optional_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'title'  => 'Updated Title',
            'status' => 'todo',
        ])->assertStatus(200);
    }

    public function test_description_cannot_be_empty_string_when_provided(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'description' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_status_is_optional_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'title' => 'Updated Title',
        ])->assertStatus(200);
    }

    public function test_status_must_be_one_of_allowed_values_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'status' => 'invalid_status',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[DataProvider('validStatusProvider')]
    public function test_each_valid_status_is_accepted_on_update(string $status): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'status' => $status,
        ])->assertStatus(200);
    }

    public static function validStatusProvider(): array
    {
        return [
            'todo status'        => ['todo'],
            'in_progress status' => ['in_progress'],
            'done status'        => ['done'],
        ];
    }

    public function test_deadline_must_be_a_valid_date_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'deadline' => 'not-a-date',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_deadline_must_be_in_the_future_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'deadline' => now()->subDay()->toDateTimeString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_deadline_can_be_set_to_null_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'deadline' => null,
        ])->assertStatus(200);
    }

    public function test_future_deadline_is_valid_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'deadline' => now()->addWeek()->toDateTimeString(),
        ])->assertStatus(200);
    }

    public function test_project_id_must_exist_in_projects_table_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'project_id' => 99999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_valid_project_id_is_accepted_on_update(): void
    {
        $user    = $this->actingAsUser();
        $task    = Task::factory()->create(['user_id' => $user->id, 'deadline' => null]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'project_id' => $project->id,
        ])->assertStatus(200);
    }

    public function test_project_id_can_be_set_to_null_on_update(): void
    {
        $task = $this->makeTask();

        $this->putJson("/api/v1/task-management/tasks/{$task->id}", [
            'project_id' => null,
        ])->assertStatus(200);
    }
}
