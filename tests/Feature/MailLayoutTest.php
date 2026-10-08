<?php

namespace Tests\Feature;

use App\Mail\ActivateAccount;
use App\Mail\ConfirmEmailChange;
use App\Mail\LikeThresholdReached;
use App\Mail\NewPostInTopic;
use App\Mail\ResetPassword;
use App\Mail\UserMentioned;
use App\Mail\WeeklyDigest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class MailLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_mails_use_the_site_background_colour(): void
    {
        $user = User::factory()->create();
        $digest = ['active_topics_count' => 0, 'new_members_count' => 0, 'new_topics' => [], 'popular_topics' => []];

        foreach ([new WeeklyDigest($user, $digest), new ConfirmEmailChange($user, 'nieuw@example.com')] as $mail) {
            $mail->assertSeeInHtml('<body bgcolor="#fcf9f6" style="background-color: #fcf9f6; margin: 0;">', false);
            $mail->assertDontSeeInHtml('#fff9f6', false);
        }
    }

    /**
     * Gmail overrides the colour of links that are only styled through the
     * <style> block, so every text link needs an inline colour.
     */
    public function test_text_links_have_an_inline_colour(): void
    {
        $user = User::factory()->create(['email_verification_token' => 'token']);
        $post = Post::factory()->create();
        $topic = ['avatar_url' => '', 'forum' => 'Algemeen', 'posts_count' => 1, 'username' => 'anna'];
        $digest = [
            'active_topics_count' => 1,
            'new_members_count' => 0,
            'new_topics' => [[...$topic, 'title' => 'Nieuw onderwerp', 'url' => url('/nieuw')]],
            'popular_topics' => [[...$topic, 'title' => 'Populair onderwerp', 'url' => url('/populair')]],
        ];

        $mails = [
            new ActivateAccount($user),
            new ConfirmEmailChange($user, 'nieuw@example.com'),
            new LikeThresholdReached($post, 10),
            new NewPostInTopic($post, $user),
            new ResetPassword($user, 'token'),
            new UserMentioned($post, $user),
            new WeeklyDigest($user, $digest),
        ];

        foreach ($mails as $mail) {
            $this->assertTextLinksHaveInlineColour($mail);
        }
    }

    protected function assertTextLinksHaveInlineColour(Mailable $mail): void
    {
        preg_match_all('/<a\s[^>]*>.*?<\/a>/s', $mail->render(), $matches);

        $textLinks = array_filter(
            $matches[0],
            fn (string $link): bool => ! str_contains($link, 'class="btn"') && ! str_contains($link, '<img'),
        );

        $this->assertNotEmpty($textLinks, $mail::class.' has no text links');

        foreach ($textLinks as $link) {
            $this->assertStringContainsString('style="color: #c93020;', $link, $mail::class.': '.$link);
        }
    }
}
