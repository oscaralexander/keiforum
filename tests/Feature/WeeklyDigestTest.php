<?php

namespace Tests\Feature;

use App\Jobs\SendWeeklyDigest;
use App\Mail\WeeklyDigest;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function newsUser(): User
    {
        return User::query()->where('username', config('news.username'))->firstOrFail();
    }

    private function createActiveTopic(array $attributes = [], int $posts = 1, ?User $author = null): Topic
    {
        $topic = Topic::factory()->create($attributes);

        Post::factory()->count($posts)->create([
            'topic_id' => $topic->id,
            ...($author ? ['user_id' => $author->id] : []),
        ]);

        return $topic;
    }

    private function createActiveTopics(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createActiveTopic();
        }
    }

    public function test_digest_is_not_sent_with_too_little_activity(): void
    {
        $this->createActiveTopics(2);
        User::factory()->create();

        (new SendWeeklyDigest)->handle();

        Mail::assertNothingQueued();
    }

    public function test_digest_is_sent_to_subscribed_members(): void
    {
        $this->createActiveTopics(3);
        $member = User::factory()->create();

        (new SendWeeklyDigest)->handle();

        Mail::assertQueued(WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->hasTo($member->email));
    }

    public function test_digest_skips_unsubscribed_unverified_banned_and_news_users(): void
    {
        $this->createActiveTopics(3);
        $unsubscribed = User::factory()->create(['is_subscribed_to_digest' => false]);
        $unverified = User::factory()->unverified()->create();
        $banned = User::factory()->create(['banned_until' => now()->addWeek()]);
        $formerlyBanned = User::factory()->create(['banned_until' => now()->subDay()]);

        (new SendWeeklyDigest)->handle();

        foreach ([$unsubscribed, $unverified, $banned, $this->newsUser()] as $user) {
            Mail::assertNotQueued(WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->hasTo($user->email));
        }

        Mail::assertQueued(WeeklyDigest::class, fn (WeeklyDigest $mail) => $mail->hasTo($formerlyBanned->email));
    }

    public function test_news_posts_do_not_count_as_activity(): void
    {
        $this->createActiveTopics(2);
        $this->createActiveTopic(['user_id' => $this->newsUser()->id], author: $this->newsUser());

        (new SendWeeklyDigest)->handle();

        Mail::assertNothingQueued();
    }

    public function test_hidden_topics_and_old_posts_do_not_count_as_activity(): void
    {
        $this->createActiveTopics(2);
        $this->createActiveTopic(['is_visible' => false]);
        Post::factory()->create(['created_at' => now()->subDays(8)]);

        (new SendWeeklyDigest)->handle();

        Mail::assertNothingQueued();
    }

    public function test_digest_contents(): void
    {
        $busiest = $this->createActiveTopic(['title' => 'Drukste onderwerp'], posts: 4);
        $this->createActiveTopic(['title' => 'Rustig onderwerp'], posts: 2);
        $this->createActiveTopic(['title' => 'Stil onderwerp'], posts: 1);
        $this->createActiveTopic(['title' => 'Nieuwsbericht', 'user_id' => $this->newsUser()->id], author: $this->newsUser());
        $oldTopic = Topic::factory()->create(['title' => 'Oud onderwerp', 'created_at' => now()->subDays(10)]);
        Post::factory()->create(['topic_id' => $oldTopic->id, 'created_at' => now()->subDays(10)]);

        $digest = (new SendWeeklyDigest)->digest(now()->subDays(7));

        $this->assertSame(3, $digest['active_topics_count']);
        $this->assertSame(['Drukste onderwerp', 'Rustig onderwerp', 'Stil onderwerp'], array_column($digest['popular_topics'], 'title'));
        $this->assertSame(4, $digest['popular_topics'][0]['posts_count']);
        $this->assertSame(route('topic.show', [$busiest->forum, $busiest, $busiest->slug]), $digest['popular_topics'][0]['url']);
        $this->assertNotContains('Nieuwsbericht', array_column($digest['new_topics'], 'title'));
        $this->assertNotContains('Oud onderwerp', array_column($digest['new_topics'], 'title'));
        $this->assertGreaterThan(0, $digest['new_members_count']);
    }

    public function test_digest_includes_absolute_avatar_url_of_topic_starter(): void
    {
        $withAvatar = User::factory()->create(['username' => 'metfoto', 'has_avatar' => true]);
        $withoutAvatar = User::factory()->create(['username' => 'zonderfoto', 'has_avatar' => false]);
        $this->createActiveTopic(['title' => 'Met foto', 'user_id' => $withAvatar->id], posts: 2);
        $this->createActiveTopic(['title' => 'Zonder foto', 'user_id' => $withoutAvatar->id]);

        $topics = collect((new SendWeeklyDigest)->digest(now()->subDays(7))['popular_topics'])->keyBy('title');

        $this->assertSame('metfoto', $topics['Met foto']['username']);
        $this->assertSame(route('img', ['src' => 'avatars/metfoto.webp', 'w' => 64, 'h' => 64, 'q' => 80, 'f' => 'jpg']), $topics['Met foto']['avatar_url']);
        $this->assertSame(asset('assets/img/avatar/z.png'), $topics['Zonder foto']['avatar_url']);
    }

    public function test_new_topics_exclude_popular_topics(): void
    {
        config(['digest.popular_topics' => 1]);
        $this->createActiveTopic(['title' => 'Populair'], posts: 3);
        $this->createActiveTopic(['title' => 'Nieuw']);

        $digest = (new SendWeeklyDigest)->digest(now()->subDays(7));

        $this->assertSame(['Populair'], array_column($digest['popular_topics'], 'title'));
        $this->assertSame(['Nieuw'], array_column($digest['new_topics'], 'title'));
    }

    public function test_mail_renders_topics_and_unsubscribe_link(): void
    {
        $user = User::factory()->create(['name' => 'Anne de Vries']);
        $digest = [
            'active_topics_count' => 3,
            'new_members_count' => 2,
            'new_topics' => [['title' => 'Nieuwe bakker', 'url' => 'https://keiforum.test/a', 'forum' => 'Algemeen', 'posts_count' => 1, 'avatar_url' => 'https://keiforum.test/avatar-a.webp', 'username' => 'bakkersfan']],
            'popular_topics' => [['title' => 'Windmolens Isselt', 'url' => 'https://keiforum.test/b', 'forum' => 'Nieuws', 'posts_count' => 5, 'avatar_url' => 'https://keiforum.test/avatar-b.webp', 'username' => 'windvanger']],
        ];

        $mail = new WeeklyDigest($user, $digest);

        $mail->assertHasSubject(__('mail/digest.subject'));
        $mail->assertSeeInHtml('Windmolens Isselt');
        $mail->assertSeeInHtml('5 nieuwe berichten');
        $mail->assertSeeInHtml('Nieuwe bakker');
        $mail->assertSeeInHtml('2 nieuwe leden');
        $mail->assertSeeInHtml('src="https://keiforum.test/avatar-b.webp"', false);
        $mail->assertSeeInHtml('src="https://keiforum.test/avatar-a.webp"', false);
        $mail->assertSeeInHtml('style="padding-right: 12px; width: 32px;"', false);
        $mail->assertSeeInHtml('height="32"', false);
        $mail->assertSeeInHtml('style="font-weight: 600;"', false);
        $mail->assertSeeInHtml('Je ontvangt deze mail omdat je lid bent van Keiforum.');
        $mail->assertSeeInHtml(e($mail->unsubscribeUrl()), false);
        $this->assertSame('<'.$mail->unsubscribeUrl().'>', $mail->headers()->text['List-Unsubscribe']);
    }

    public function test_unsubscribe_link_requires_valid_signature(): void
    {
        $user = User::factory()->create();

        $this->get(route('digest.unsubscribe', $user))->assertForbidden();
        $this->get(URL::signedRoute('digest.unsubscribe', $user))->assertOk();

        $this->assertTrue($user->fresh()->is_subscribed_to_digest);
    }

    public function test_user_can_unsubscribe_and_resubscribe(): void
    {
        $user = User::factory()->create();

        Livewire::test('pages::user.unsubscribe-digest', ['user' => $user])
            ->call('unsubscribe')
            ->assertSee(__('user/unsubscribe_digest.resubscribe'));

        $this->assertFalse($user->fresh()->is_subscribed_to_digest);

        Livewire::test('pages::user.unsubscribe-digest', ['user' => $user->fresh()])
            ->call('resubscribe');

        $this->assertTrue($user->fresh()->is_subscribed_to_digest);
    }

    public function test_user_can_toggle_digest_in_settings(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->assertSet('isSubscribedToDigest', true)
            ->set('isSubscribedToDigest', false)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertFalse($user->fresh()->is_subscribed_to_digest);
    }

    public function test_settings_page_shows_digest_toggle_above_save_button(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertOk()
            ->assertSeeInOrder([
                __('user/settings.form.email.label'),
                __('user/settings.notifications.digest'),
                __('ui.save'),
            ]);
    }

    public function test_digest_is_scheduled_on_sunday_morning(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->description ?? '', 'SendWeeklyDigest'));

        $this->assertNotNull($event);
        $this->assertSame('0 10 * * 0', $event->expression);
    }
}
