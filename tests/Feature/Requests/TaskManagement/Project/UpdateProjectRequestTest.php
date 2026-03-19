<?php

namespace Tests\Feature\Requests\TaskManagement\Project;

use App\Models\TaskManagement\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProjectRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_optional_on_update(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'description' => 'Updated description',
        ])->assertStatus(200);
    }

    public function test_name_cannot_be_empty_string_when_provided(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => '',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_cannot_exceed_255_characters_on_update(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => str_repeat('a', 256),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_must_be_a_string_on_update(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => 99999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_description_is_optional_on_update(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => 'Updated Name',
        ])->assertStatus(200);
    }

    public function test_description_can_be_set_to_null_on_update(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'description' => null,
        ])->assertStatus(200);
    }

    public function test_valid_payload_updates_project(): void
    {
        $user = $this->actingAsUser();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/v1/task-management/projects/$project->id", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ])->assertStatus(200)
            ->assertJsonPath('project.name', 'Updated Name');
    }
}
