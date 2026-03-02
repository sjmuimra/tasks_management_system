<?php

namespace Tests\Feature\Requests\TaskManagement\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/register';

    public function test_name_is_required(): void
    {
        $this->postJson($this->url, [
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_must_be_a_string(): void
    {
        $this->postJson($this->url, [
            'name' => 12345,
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_cannot_exceed_255_characters(): void
    {
        $this->postJson($this->url, [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_of_255_characters_is_valid(): void
    {
        $this->postJson($this->url, [
            'name' => str_repeat('a', 255),
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);
    }

    public function test_email_is_required(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_a_valid_email_address(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_cannot_exceed_255_characters(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => str_repeat('a', 250).'@b.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_required(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_all_fields_are_required(): void
    {
        $this->postJson($this->url, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_valid_payload_passes_validation_and_creates_user(): void
    {
        $this->postJson($this->url, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }
}
