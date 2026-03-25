<?php

namespace Tests\Feature\Posts;

use App\Enums\ReportType;
use App\Livewire\Posts\ReportModal;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_report(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportModal::class, ['postId' => $post->id])
            ->set('type', ReportType::CONTENT->value)
            ->set('comment', 'This is spam.')
            ->call('submit')
            ->assertSet('isSubmitted', true);

        $this->assertDatabaseHas('reports', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => ReportType::CONTENT->value,
            'comment' => 'This is spam.',
        ]);
    }

    public function test_report_without_comment_is_allowed(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportModal::class, ['postId' => $post->id])
            ->set('type', ReportType::BEHAVIOR->value)
            ->call('submit')
            ->assertSet('isSubmitted', true);

        $this->assertDatabaseHas('reports', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => ReportType::BEHAVIOR->value,
            'comment' => null,
        ]);
    }

    public function test_submit_requires_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportModal::class, ['postId' => $post->id])
            ->call('submit')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_submit_rejects_invalid_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportModal::class, ['postId' => $post->id])
            ->set('type', 'invalid-type')
            ->call('submit')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_guest_cannot_submit_report(): void
    {
        $post = Post::factory()->create();

        Livewire::test(ReportModal::class, ['postId' => $post->id])
            ->set('type', ReportType::OTHER->value)
            ->call('submit')
            ->assertForbidden();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_all_report_types_are_rendered(): void
    {
        $post = Post::factory()->create();

        $component = Livewire::test(ReportModal::class, ['postId' => $post->id]);

        foreach (ReportType::cases() as $type) {
            $component->assertSee($type->label());
        }
    }

    public function test_confirmation_message_shown_after_submit(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportModal::class, ['postId' => $post->id])
            ->set('type', ReportType::VIOLATION->value)
            ->call('submit')
            ->assertSee(__('post/report.confirmation'));
    }
}
