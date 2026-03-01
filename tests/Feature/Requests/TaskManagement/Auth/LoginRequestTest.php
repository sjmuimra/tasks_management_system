<?php

namespace Tests\Feature\Requests\TaskManagement\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/login';

    public function test_email_is_required(): void
    {
        $this->postJson($this->url, [
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_a_valid_email_address(): void
    {
        $this->postJson($this->url, [
            'email'    => 'not-an-email',
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_a_string(): void
    {
        $this->postJson($this->url, [
            'email'    => 12345,
            'password' => 'password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_required(): void
    {
        $this->postJson($this->url, [
            'email' => 'john@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_a_string(): void
    {
        $this->postJson($this->url, [
            'email'    => 'john@example.com',
            'password' => 12345678,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_all_fields_are_required(): void
    {
        $this->postJson($this->url, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_valid_payload_passes_validation(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson($this->url, [
            'email'    => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }
}
