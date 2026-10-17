<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weekly digest
    |--------------------------------------------------------------------------
    |
    | Every Sunday, members get an email with the most active and newest
    | topics of the past week, also in quiet weeks.
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

    'popular_topics' => 5,

    'new_topics' => 5,

    'send_interval' => env('DIGEST_SEND_INTERVAL', 2),

];
