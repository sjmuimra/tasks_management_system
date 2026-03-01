<?php

namespace Tests\Feature\Requests\TaskManagement\Task;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\TaskManagement\Project;
use Tests\TestCase;

class StoreTaskRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/task-management/tasks';

    public function test_title_is_required(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_must_be_a_string(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 12345,
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_cannot_exceed_255_characters(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => str_repeat('a', 256),
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_title_of_255_characters_is_valid(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => str_repeat('a', 255),
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(201);
    }

    public function test_description_is_required(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'  => 'My Task',
            'status' => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_description_must_be_a_string(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 12345,
            'status'      => 'todo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_status_is_required(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_status_must_be_one_of_allowed_values(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'invalid_status',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    #[DataProvider('validStatusProvider')]
    public function test_each_valid_status_is_accepted(string $status): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => $status,
        ])->assertStatus(201);
    }

    public static function validStatusProvider(): array
    {
        return [
            'todo status'        => ['todo'],
            'in_progress status' => ['in_progress'],
            'done status'        => ['done'],
        ];
    }

    public function test_deadline_is_optional(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(201);
    }

    public function test_deadline_must_be_a_valid_date(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'deadline'    => 'not-a-date',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_deadline_must_be_in_the_future(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'deadline'    => now()->subDay()->toDateTimeString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_deadline_can_be_null(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'deadline'    => null,
        ])->assertStatus(201);
    }

    public function test_future_deadline_is_valid(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'deadline'    => now()->addWeek()->toDateTimeString(),
        ])->assertStatus(201);
    }

    public function test_project_id_is_optional(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
        ])->assertStatus(201);
    }

    public function test_project_id_must_exist_in_projects_table(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'project_id'  => 99999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_valid_project_id_is_accepted(): void
    {
        $user    = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->postJson($this->url, [
            'title'       => 'My Task',
            'description' => 'desc',
            'status'      => 'todo',
            'project_id'  => $project->id,
        ])->assertStatus(201);
    }
}
