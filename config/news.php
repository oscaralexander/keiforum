<?php

return [

    /*
    |--------------------------------------------------------------------------
    | News
    |--------------------------------------------------------------------------
    |
    | Articles from the Nieuwsplein33 RSS feed are evaluated by Claude and
    | posted as topics in the news forum by the news user.
    |
    */

    'feed_url' => env('NEWS_FEED_URL', 'https://www.nieuwsplein33.nl/rss/nieuws.xml'),

    'forum_slug' => env('NEWS_FORUM_SLUG', 'nieuws'),

    'username' => env('NEWS_USERNAME', 'nieuwsplein33'),

    'model' => env('NEWS_MODEL', 'claude-opus-5-5'),

    /*
    |--------------------------------------------------------------------------
    | Article image
    |--------------------------------------------------------------------------
    |
    | Show the lead image of the Nieuwsplein33 article, with its caption and
    | copyright credit, above the opening post of news topics. Turning this
    | off hides the image on all news topics right away.
    |
    */

    'show_article_image' => env('NEWS_SHOW_ARTICLE_IMAGE', true),

];
