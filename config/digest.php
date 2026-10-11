<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weekly digest
    |--------------------------------------------------------------------------
    |
    | Every Sunday, members get an email with the most active and newest
    | topics of the past week. No email is sent when fewer than
    | `min_active_topics` topics had a post by a member that week.
    |
    | Topics come from the `forums` in order: the first forum fills the
    | lists, later forums only pad them. Forums not listed are left out,
    | also when counting activity.
    |
    | The emails are spaced `send_interval` seconds apart to stay within
    | the mail provider's rate limit.
    |
    */

    'days' => 7,

    'forums' => ['algemeen', 'nieuws'],

    'min_active_topics' => env('DIGEST_MIN_ACTIVE_TOPICS', 3),

    'popular_topics' => 5,

    'new_topics' => 5,

    'send_interval' => env('DIGEST_SEND_INTERVAL', 2),

];
