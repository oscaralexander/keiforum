<?php

namespace Tests\Feature\Pages\Admin;

use App\Enums\ReportType;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_is_accessible_to_admin_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin'))
            ->assertOk();
    }

    public function test_admin_page_is_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin'))
            ->assertForbidden();
    }

    public function test_admin_page_redirects_guests(): void
    {
        $this->get(route('admin'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_page_shows_post_once_with_multiple_reports(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();
        Report::factory()->create(['post_id' => $post->id, 'type' => ReportType::CONTENT]);
        Report::factory()->create(['post_id' => $post->id, 'type' => ReportType::BEHAVIOR]);

        $html = $this->actingAs($admin)->get(route('admin'))->getContent();

        $this->assertEquals(1, substr_count($html, 'id="post-'.$post->id.'"'));
    }

    public function test_post_component_shows_all_report_type_labels_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();
        Report::factory()->create(['post_id' => $post->id, 'type' => ReportType::CONTENT]);
        Report::factory()->create(['post_id' => $post->id, 'type' => ReportType::BEHAVIOR]);

        Livewire::actingAs($admin)
            ->test('post', ['post' => $post, 'number' => 1])
            ->assertSee(ReportType::CONTENT->label())
            ->assertSee(ReportType::BEHAVIOR->label());
    }

    public function test_post_component_shows_reporter_usernames_for_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $post = Post::factory()->create();
        Report::factory()->create(['post_id' => $post->id, 'user_id' => $reporter->id]);

        Livewire::actingAs($admin)
            ->test('post', ['post' => $post, 'number' => 1])
            ->assertSee($reporter->username);
    }

    public function test_post_component_hides_report_bar_for_regular_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $post = Post::factory()->create();
        Report::factory()->create(['post_id' => $post->id, 'type' => ReportType::CONTENT]);

        Livewire::actingAs($user)
            ->test('post', ['post' => $post, 'number' => 1])
            ->assertDontSee(ReportType::CONTENT->label());
    }

    public function test_delete_reports_removes_all_reports_for_post(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $post = Post::factory()->create();
        Report::factory()->count(2)->create(['post_id' => $post->id]);

        Livewire::actingAs($admin)
            ->test('post', ['post' => $post, 'number' => 1])
            ->call('deleteReports');

        $this->assertEquals(0, Report::where('post_id', $post->id)->count());
    }

    public function test_admin_page_orders_posts_by_report_count_then_latest_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $postWithOneReport = Post::factory()->create();
        Report::factory()->create(['post_id' => $postWithOneReport->id, 'created_at' => now()->subDays(1)]);

        $postWithTwoReports = Post::factory()->create();
        Report::factory()->create(['post_id' => $postWithTwoReports->id, 'created_at' => now()->subDays(3)]);
        Report::factory()->create(['post_id' => $postWithTwoReports->id, 'created_at' => now()->subDays(2)]);

        $postWithOneNewerReport = Post::factory()->create();
        Report::factory()->create(['post_id' => $postWithOneNewerReport->id, 'created_at' => now()]);

        $html = $this->actingAs($admin)->get(route('admin'))->getContent();

        $this->assertLessThan(
            strpos($html, 'post-'.$postWithOneReport->id),
            strpos($html, 'post-'.$postWithTwoReports->id),
            'Post with more reports should appear first'
        );

        $this->assertLessThan(
            strpos($html, 'post-'.$postWithOneReport->id),
            strpos($html, 'post-'.$postWithOneNewerReport->id),
            'Post with a newer report should appear before post with an older report'
        );
    }

    public function test_delete_reports_is_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $post = Post::factory()->create();
        Report::factory()->create(['post_id' => $post->id]);

        Livewire::actingAs($user)
            ->test('post', ['post' => $post, 'number' => 1])
            ->call('deleteReports')
            ->assertForbidden();
    }
}
