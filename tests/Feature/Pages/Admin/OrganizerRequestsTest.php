<?php

namespace Tests\Feature\Pages\Admin;

use App\Mail\OrganizerRequestDecided;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizerRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_lists_pending_requests_oldest_first(): void
    {
        $newer = User::factory()->create(['organizer_requested_at' => now()->subDay()]);
        $older = User::factory()->create(['organizer_requested_at' => now()->subWeek()]);
        $organizer = User::factory()->organizer()->create(['organizer_requested_at' => now()]);
        $other = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin'))
            ->assertOk()
            ->assertSee(__('admin/index.organizer_requests.title'))
            ->assertSeeInOrder(['@'.$older->username, '@'.$newer->username])
            ->assertDontSee('@'.$organizer->username)
            ->assertDontSee('@'.$other->username);
    }

    public function test_admin_page_shows_a_message_without_requests(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin'))
            ->assertSee(__('admin/index.organizer_requests.empty'));
    }

    public function test_approving_makes_the_user_an_organizer_and_mails_them(): void
    {
        Mail::fake();
        $user = User::factory()->create(['organizer_requested_at' => now()]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('admin.organizer-requests')
            ->call('approve', $user->id)
            ->assertDontSee('@'.$user->username);

        $user->refresh();
        $this->assertTrue($user->is_organizer);
        $this->assertNull($user->organizer_requested_at);

        Mail::assertSent(OrganizerRequestDecided::class, fn (OrganizerRequestDecided $mail): bool => $mail->hasTo($user->email)
            && $mail->isApproved
            && $mail->hasSubject(__('mail/user.organizer-approved.subject'))
            && str_contains($mail->render(), route('agenda.create')));
    }

    public function test_denying_clears_the_request_and_mails_the_user(): void
    {
        Mail::fake();
        $user = User::factory()->create(['organizer_requested_at' => now()]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('admin.organizer-requests')
            ->call('deny', $user->id);

        $user->refresh();
        $this->assertFalse($user->is_organizer);
        $this->assertNull($user->organizer_requested_at);

        Mail::assertSent(OrganizerRequestDecided::class, fn (OrganizerRequestDecided $mail): bool => $mail->hasTo($user->email)
            && ! $mail->isApproved
            && $mail->hasSubject(__('mail/user.organizer-denied.subject')));
    }

    public function test_a_user_without_a_pending_request_cannot_be_decided(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        try {
            Livewire::actingAs(User::factory()->admin()->create())
                ->test('admin.organizer-requests')
                ->call('approve', $user->id);

            $this->fail('A user without a pending request was approved.');
        } catch (ModelNotFoundException) {
            $this->assertFalse($user->refresh()->is_organizer);
            Mail::assertNothingSent();
        }
    }

    public function test_members_cannot_decide_requests(): void
    {
        $user = User::factory()->create(['organizer_requested_at' => now()]);

        Livewire::actingAs(User::factory()->create())
            ->test('admin.organizer-requests')
            ->assertForbidden();

        $this->assertFalse($user->refresh()->is_organizer);
    }
}
