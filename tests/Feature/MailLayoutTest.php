<?php

namespace Tests\Feature;

use App\Mail\ConfirmEmailChange;
use App\Mail\WeeklyDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
