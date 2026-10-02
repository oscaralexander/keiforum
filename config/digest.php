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
    */

    'days' => 7,

    'min_active_topics' => env('DIGEST_MIN_ACTIVE_TOPICS', 3),

    'popular_topics' => 5,

    'new_topics' => 5,

];
