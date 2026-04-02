<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PollTest extends TestCase
{
    use RefreshDatabase;

    private function createTopicWithPoll(): array
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        Post::factory()->create(['topic_id' => $topic->id]);
        $poll = Poll::create(['topic_id' => $topic->id, 'question' => 'Wat is je favoriete kleur?']);
        $optionA = PollOption::create(['poll_id' => $poll->id, 'label' => 'Blauw', 'order' => 0]);
        $optionB = PollOption::create(['poll_id' => $poll->id, 'label' => 'Rood', 'order' => 1]);

        return [$forum, $topic, $poll, $optionA, $optionB];
    }

    // --- Create form ---

    public function test_poll_toggle_not_shown_for_marketplace_forum(): void
    {
        $marketplaceForum = Forum::factory()->create(['id' => 2]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $marketplaceForum])
            ->assertDontSee(__('topic/form.poll.label'));
    }

    public function test_adding_poll_options_works(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('poll.active', true)
            ->assertSet('poll.active', true)
            ->assertCount('poll.options', 2)
            ->call('addPollOption')
            ->assertCount('poll.options', 3);
    }

    public function test_removing_poll_option_works(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('poll.active', true);

        $id = $component->get('poll.options')[0]['id'];

        $component->call('removePollOption', $id)
            ->assertCount('poll.options', 1);
    }

    public function test_reorder_poll_options_works(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('poll.active', true)
            ->call('addPollOption');

        $options = $component->get('poll.options');
        $firstId = $options[0]['id'];

        // Move the first option to position 2 (last)
        $component->call('reorderPollOptions', $firstId, 2);

        $reordered = $component->get('poll.options');
        $this->assertSame($firstId, $reordered[2]['id']);
    }

    public function test_creating_topic_with_poll_saves_poll_and_options(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('title', 'Poll onderwerp')
            ->set('body', '<p>Test</p>')
            ->set('poll.active', true)
            ->set('poll.question', 'Wat is je lievelingsdier?')
            ->set('poll.options.0.label', 'Hond')
            ->set('poll.options.1.label', 'Kat')
            ->call('submit')
            ->assertRedirect();

        $topic = Topic::where('title', 'Poll onderwerp')->first();
        $this->assertNotNull($topic);
        $this->assertNotNull($topic->poll);
        $this->assertSame('Wat is je lievelingsdier?', $topic->poll->question);
        $this->assertCount(2, $topic->poll->options);
    }

    public function test_creating_topic_without_poll_creates_no_poll(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('title', 'Gewoon onderwerp')
            ->set('body', '<p>Test</p>')
            ->call('submit')
            ->assertRedirect();

        $topic = Topic::where('title', 'Gewoon onderwerp')->first();
        $this->assertNull($topic->poll);
    }

    public function test_poll_validation_requires_question_when_poll_enabled(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('title', 'Poll onderwerp')
            ->set('body', '<p>Test</p>')
            ->set('poll.active', true)
            ->set('poll.question', '')
            ->set('poll.options.0.label', 'Hond')
            ->set('poll.options.1.label', 'Kat')
            ->call('submit')
            ->assertHasErrors(['poll.question']);
    }

    public function test_poll_validation_requires_at_least_two_options(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('title', 'Poll onderwerp')
            ->set('body', '<p>Test</p>')
            ->set('poll.active', true)
            ->set('poll.question', 'Een vraag?')
            ->set('poll.options', [['id' => Str::uuid()->toString(), 'label' => 'Alleen optie', 'isLocked' => false]])
            ->call('submit')
            ->assertHasErrors(['poll.options']);
    }

    // --- Voting component ---

    public function test_poll_component_shows_question(): void
    {
        [$forum, $topic, $poll] = $this->createTopicWithPoll();

        Livewire::test('poll', ['topic' => $topic])
            ->assertSee('Wat is je favoriete kleur?');
    }

    public function test_guest_sees_options_but_not_results(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();

        Livewire::test('poll', ['topic' => $topic])
            ->assertSee('Blauw')
            ->assertSee('Log in', false)
            ->assertSee('om te stemmen', false);
    }

    public function test_authenticated_user_who_has_not_voted_sees_vote_form(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->assertSee(__('poll.vote'))
            ->assertDontSee(__('poll.voted'));
    }

    public function test_authenticated_user_can_vote(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->set('selectedOption', $optionA->id)
            ->call('vote');

        $this->assertDatabaseHas('poll_votes', [
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_sees_results_after_voting(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->assertSee(__('poll.change_vote'))
            ->assertDontSee(__('poll.vote'));
    }

    public function test_user_can_change_vote(): void
    {
        [$forum, $topic, $poll, $optionA, $optionB] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->call('edit')
            ->assertSet('isUpdating', true)
            ->set('selectedOption', $optionB->id)
            ->call('vote')
            ->assertSet('isUpdating', false);

        $this->assertDatabaseMissing('poll_votes', ['poll_option_id' => $optionA->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('poll_votes', ['poll_option_id' => $optionB->id, 'user_id' => $user->id]);
        $this->assertCount(1, PollVote::where('user_id', $user->id)->get());
    }

    public function test_change_vote_button_preselects_current_vote(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->call('edit')
            ->assertSet('selectedOption', $optionA->id);
    }

    public function test_change_vote_shows_correct_button_label(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->call('edit')
            ->assertSee(__('poll.vote'))
            ->assertDontSee(__('poll.change_vote'));
    }

    public function test_voted_option_is_highlighted_in_results(): void
    {
        [$forum, $topic, $poll, $optionA, $optionB] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        $html = Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->html();

        $this->assertStringContainsString('poll__result--voted', $html);
    }

    public function test_voting_directly_without_start_change_vote_replaces_existing_vote(): void
    {
        [$forum, $topic, $poll, $optionA, $optionB] = $this->createTopicWithPoll();
        $user = User::factory()->create();

        // calling vote() directly (without startChangeVote) on an already-voted poll
        // should still replace the existing vote, since vote() always deletes first
        PollVote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionA->id,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->set('selectedOption', $optionB->id)
            ->call('vote');

        $this->assertCount(1, PollVote::where('user_id', $user->id)->get());
        $this->assertDatabaseHas('poll_votes', ['poll_option_id' => $optionB->id, 'user_id' => $user->id]);
    }

    public function test_guest_cannot_vote(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();

        Livewire::test('poll', ['topic' => $topic])
            ->set('selectedOption', $optionA->id)
            ->call('vote')
            ->assertForbidden();
    }

    public function test_user_cannot_vote_on_option_from_different_poll(): void
    {
        [$forum, $topic, $poll, $optionA] = $this->createTopicWithPoll();

        $otherTopic = Topic::factory()->create(['forum_id' => $forum->id]);
        Post::factory()->create(['topic_id' => $otherTopic->id]);
        $otherPoll = Poll::create(['topic_id' => $otherTopic->id, 'question' => 'Andere vraag?']);
        $otherOption = PollOption::create(['poll_id' => $otherPoll->id, 'label' => 'Anders', 'order' => 0]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('poll', ['topic' => $topic])
            ->set('selectedOption', $otherOption->id)
            ->call('vote');

        $this->assertDatabaseMissing('poll_votes', ['user_id' => $user->id]);
    }

    public function test_topic_show_page_shows_poll_when_present(): void
    {
        [$forum, $topic, $poll] = $this->createTopicWithPoll();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSeeLivewire('poll');
    }

    public function test_topic_show_page_does_not_show_poll_when_absent(): void
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        Post::factory()->create(['topic_id' => $topic->id]);

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertDontSeeLivewire('poll');
    }

    // --- Poll editing via first post ---

    private function createTopicWithPollAndAuthor(): array
    {
        $forum = Forum::factory()->create();
        $author = User::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        $firstPost = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);
        $poll = Poll::create(['topic_id' => $topic->id, 'question' => 'Originele vraag?']);
        $optionA = PollOption::create(['poll_id' => $poll->id, 'label' => 'Optie A', 'order' => 0]);
        $optionB = PollOption::create(['poll_id' => $poll->id, 'label' => 'Optie B', 'order' => 1]);

        return [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author];
    }

    public function test_editing_first_post_loads_poll_fields(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->assertSet('poll.active', true)
            ->assertSet('poll.question', 'Originele vraag?')
            ->assertCount('poll.options', 2);
    }

    public function test_editing_first_post_without_poll_does_not_set_is_poll_editing(): void
    {
        $forum = Forum::factory()->create();
        $author = User::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id, 'user_id' => $author->id]);
        $firstPost = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->assertSet('poll.active', false);
    }

    public function test_saving_first_post_updates_poll_question(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->set('poll.question', 'Nieuwe vraag?')
            ->call('submit');

        $this->assertDatabaseHas('polls', ['id' => $poll->id, 'question' => 'Nieuwe vraag?']);
    }

    public function test_unlocked_option_label_can_be_edited(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->set('poll.options.0.label', 'Gewijzigde optie A')
            ->call('submit');

        $this->assertDatabaseHas('poll_options', ['id' => $optionA->id, 'label' => 'Gewijzigde optie A']);
    }

    public function test_locked_option_label_cannot_be_changed(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();
        $otherUser = User::factory()->create();

        // Another user votes on optionA — it becomes locked
        PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $optionA->id, 'user_id' => $otherUser->id]);

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->assertSet('poll.options.0.isLocked', true)
            ->set('poll.options.0.label', 'Probeer te wijzigen')
            ->call('submit');

        // Label must be unchanged
        $this->assertDatabaseHas('poll_options', ['id' => $optionA->id, 'label' => 'Optie A']);
    }

    public function test_option_voted_only_by_author_is_not_locked(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();

        // Author votes on optionA — should still be editable
        PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $optionA->id, 'user_id' => $author->id]);

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->assertSet('poll.options.0.isLocked', false);
    }

    public function test_unlocked_option_can_be_removed(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();
        $optionC = PollOption::create(['poll_id' => $poll->id, 'label' => 'Optie C', 'order' => 2]);

        $component = Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit');

        $idToRemove = (string) $optionA->id;

        $component->call('removePollOption', $idToRemove)
            ->call('submit');

        $this->assertDatabaseMissing('poll_options', ['id' => $optionA->id]);
    }

    public function test_locked_option_cannot_be_removed(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();
        $otherUser = User::factory()->create();

        PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $optionA->id, 'user_id' => $otherUser->id]);

        $component = Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit');

        $component->call('removePollOption', (string) $optionA->id)
            ->call('submit');

        $this->assertDatabaseHas('poll_options', ['id' => $optionA->id]);
    }

    public function test_new_option_can_be_added_while_editing(): void
    {
        [$forum, $topic, $firstPost, $poll, $optionA, $optionB, $author] = $this->createTopicWithPollAndAuthor();

        Livewire::actingAs($author)
            ->test('post', ['post' => $firstPost, 'isFirstPost' => true, 'number' => 1])
            ->call('edit')
            ->call('addPollOption')
            ->assertCount('poll.options', 3)
            ->set('poll.options.2.label', 'Nieuwe optie')
            ->call('submit');

        $this->assertDatabaseHas('poll_options', ['poll_id' => $poll->id, 'label' => 'Nieuwe optie']);
    }
}
