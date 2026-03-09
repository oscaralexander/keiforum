<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $post));
    }

    public function test_admin_can_update_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->assertTrue($admin->can('update', $post));
    }

    public function test_other_user_cannot_update_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($user->can('update', $post));
    }

    public function test_owner_can_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $post));
    }

    public function test_admin_can_delete_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $this->assertTrue($admin->can('delete', $post));
    }

    public function test_other_user_cannot_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($user->can('delete', $post));
    }

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Post::class));
    }
}
