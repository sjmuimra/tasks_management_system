<?php

namespace Tests\Unit\Models;

use App\Models\TaskManagement\Project;
use App\Models\TaskManagement\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes_are_correct(): void
    {
        $user = new User();

        $this->assertEquals(
            ['name', 'email', 'password', 'role'],
            $user->getFillable()
        );
    }

    public function test_password_is_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_remember_token_is_hidden(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_password_is_hashed_when_set(): void
    {
        $user = User::factory()->create(['password' => 'plaintext']);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(\Hash::check('plaintext', $user->password));
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2025-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
    }

    public function test_user_has_many_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->tasks);
        $this->assertInstanceOf(Task::class, $user->tasks->first());
    }

    public function test_user_has_many_projects(): void
    {
        $user = User::factory()->create();
        Project::factory(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->projects);
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }

    public function test_user_tasks_are_empty_when_no_tasks_assigned(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->tasks);
    }

    public function test_user_projects_are_empty_when_no_projects_assigned(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->projects);
    }

    public function test_deleting_user_cascades_to_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('tasks', ['user_id' => $user->id]);
    }

    public function test_deleting_user_cascades_to_projects(): void
    {
        $user = User::factory()->create();
        Project::factory(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('projects', ['user_id' => $user->id]);
    }

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->isAdmin());
    }

    public function test_is_admin_returns_false_for_user_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->assertFalse($user->isAdmin());
    }

    public function test_default_role_is_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('user', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_factory_state_creates_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertEquals('admin', $admin->role);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_user_is_persisted_to_database(): void
    {
        $user = User::factory()->create([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_email_is_unique(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'john@example.com']);
    }
}
