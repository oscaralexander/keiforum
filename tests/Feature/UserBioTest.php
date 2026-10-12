<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBioTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_is_turned_into_link_without_scheme_and_www(): void
    {
        $user = User::factory()->make(['bio' => 'Kijk op https://www.ufomeldpunt.nl/']);

        $this->assertSame(
            'Kijk op <a href="https://www.ufomeldpunt.nl/" rel="nofollow noopener ugc" target="_blank">ufomeldpunt.nl</a>',
            $user->bio_html
        );
    }

    public function test_url_without_www_keeps_its_path(): void
    {
        $user = User::factory()->make(['bio' => 'http://example.com/foo/bar?a=1&b=2']);

        $this->assertSame(
            '<a href="http://example.com/foo/bar?a=1&amp;b=2" rel="nofollow noopener ugc" target="_blank">example.com/foo/bar?a=1&amp;b=2</a>',
            $user->bio_html
        );
    }

    public function test_trailing_punctuation_is_not_part_of_the_link(): void
    {
        $user = User::factory()->make(['bio' => 'Zie https://keiforum.nl.']);

        $this->assertSame(
            'Zie <a href="https://keiforum.nl" rel="nofollow noopener ugc" target="_blank">keiforum.nl</a>.',
            $user->bio_html
        );
    }

    public function test_html_is_escaped_and_line_breaks_are_kept(): void
    {
        $user = User::factory()->make(['bio' => "<script>alert(1)</script>\nhttps://example.com/\"onclick=\"x"]);

        $this->assertSame(
            "&lt;script&gt;alert(1)&lt;/script&gt;<br />\n<a href=\"https://example.com/\" rel=\"nofollow noopener ugc\" target=\"_blank\">example.com</a>&quot;onclick=&quot;x",
            $user->bio_html
        );
    }

    public function test_bio_without_urls_is_left_as_text(): void
    {
        $user = User::factory()->make(['bio' => 'Hallo.Ik ben het, bijv. op mail@example.com of v.d.Berg.']);

        $this->assertSame('Hallo.Ik ben het, bijv. op mail@example.com of v.d.Berg.', $user->bio_html);
    }

    public function test_profile_page_shows_bio_link_to_members(): void
    {
        $user = User::factory()->create(['bio' => 'https://www.ufomeldpunt.nl/']);

        $this->actingAs($user)
            ->get(route('member.show', $user))
            ->assertOk()
            ->assertSee('<a href="https://www.ufomeldpunt.nl/" rel="nofollow noopener ugc" target="_blank">ufomeldpunt.nl</a>', false);
    }

    public function test_www_url_without_scheme_is_linked_with_https(): void
    {
        $user = User::factory()->make(['bio' => 'Kijk op www.ufomeldpunt.nl/meldingen, echt waar.']);

        $this->assertSame(
            'Kijk op <a href="https://www.ufomeldpunt.nl/meldingen" rel="nofollow noopener ugc" target="_blank">ufomeldpunt.nl/meldingen</a>, echt waar.',
            $user->bio_html
        );
    }

    public function test_bare_domain_is_linked_with_https(): void
    {
        $user = User::factory()->make(['bio' => "keiforum.nl/\n(zie ook blog.example.com)"]);

        $this->assertSame(
            "<a href=\"https://keiforum.nl/\" rel=\"nofollow noopener ugc\" target=\"_blank\">keiforum.nl</a><br />\n(zie ook <a href=\"https://blog.example.com\" rel=\"nofollow noopener ugc\" target=\"_blank\">blog.example.com</a>)",
            $user->bio_html
        );
    }

    public function test_bare_domain_with_unknown_tld_is_not_linked(): void
    {
        $user = User::factory()->make(['bio' => 'bestand.txt en zie.je']);

        $this->assertSame('bestand.txt en zie.je', $user->bio_html);
    }
}
