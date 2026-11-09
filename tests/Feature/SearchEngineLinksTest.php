<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guests following links to pages that require logging in end up on the
 * login page, so search engines should not follow those links.
 */
class SearchEngineLinksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: callable(): string, 1: callable(): string}>
     */
    public static function pagesWithCreateButton(): array
    {
        return [
            'home' => [fn () => route('home'), fn () => route('topic.create')],
            'forum' => [
                fn () => route('forum.show', Forum::factory()->create(['slug' => 'testforum'])),
                fn () => route('topic.create', Forum::query()->where('slug', 'testforum')->firstOrFail()),
            ],
            'agenda' => [fn () => route('agenda'), fn () => route('agenda.create')],
        ];
    }

    #[DataProvider('pagesWithCreateButton')]
    public function test_create_buttons_are_nofollow_for_guests(callable $pageUrl, callable $createUrl): void
    {
        $page = $pageUrl();

        $links = $this->linksTo($this->get($page)->assertOk()->getContent(), $createUrl());

        $this->assertNotEmpty($links);

        foreach ($links as $link) {
            $this->assertStringContainsString('rel="nofollow"', $link);
        }
    }

    #[DataProvider('pagesWithCreateButton')]
    public function test_create_buttons_are_followable_for_members(callable $pageUrl, callable $createUrl): void
    {
        $page = $pageUrl();

        $html = $this->actingAs(User::factory()->create())->get($page)->assertOk()->getContent();
        $links = $this->linksTo($html, $createUrl());

        $this->assertNotEmpty($links);

        foreach ($links as $link) {
            $this->assertStringNotContainsString('nofollow', $link);
        }
    }

    /**
     * The opening tags of the links in the HTML that point to the URL.
     *
     * @return list<string>
     */
    private function linksTo(string $html, string $url): array
    {
        preg_match_all('/<a\s[^>]*href="'.preg_quote(e($url), '/').'"[^>]*>/', $html, $matches);

        return $matches[0];
    }

    public function test_account_pages_are_not_indexed(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('<meta content="noindex" name="robots">', false);
    }
}
