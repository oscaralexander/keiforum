<?php

namespace App\Listeners;

use App\Events\PostSaving;
use App\Mail\UserMentioned;
use App\Models\User;
use DOMDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendMentionNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PostSaving $event): void
    {
        $post = $event->post;
        $newMentions = $this->parseMentionedUsernames($post->body);
        $oldMentions = $event->oldBody ? $this->parseMentionedUsernames($event->oldBody) : [];
        $usernames = array_values(array_diff($newMentions, $oldMentions));

        if (empty($usernames)) {
            return;
        }

        $mentionedUsers = User::query()
            ->whereIn('username', $usernames)
            ->where('id', '!=', $post->user_id)
            ->get();

        foreach ($mentionedUsers as $user) {
            Mail::to($user->email)->send(new UserMentioned($post, $user));
        }
    }

    private function parseMentionedUsernames(string $body): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $usernames = [];

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            if ($anchor->getAttribute('data-mention') !== 'true') {
                continue;
            }

            $href = $anchor->getAttribute('href');

            if (!str_starts_with($href, '/@')) {
                continue;
            }

            $usernames[] = ltrim(substr($href, 1), '@');
        }

        return array_unique($usernames);
    }
}
