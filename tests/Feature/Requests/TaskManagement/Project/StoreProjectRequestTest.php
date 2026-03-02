<?php

namespace Tests\Feature\Requests\TaskManagement\Project;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProjectRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/task-management/projects';

    public function test_name_is_required(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_must_be_a_string(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => 12345,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_cannot_exceed_255_characters(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => str_repeat('a', 256),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_of_255_characters_is_valid(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => str_repeat('a', 255),
        ])->assertStatus(201);
    }

    public function test_description_is_optional(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => 'My Project',
        ])->assertStatus(201);
    }

    public function test_description_must_be_a_string_when_provided(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => 'My Project',
            'description' => 12345,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_description_can_be_null(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => 'My Project',
            'description' => null,
        ])->assertStatus(201);
    }

    public function test_valid_payload_creates_project(): void
    {
        $this->actingAsUser();

        $this->postJson($this->url, [
            'name' => 'My Project',
            'description' => 'A description',
        ])->assertStatus(201)
            ->assertJsonPath('project.name', 'My Project');
    }
}
