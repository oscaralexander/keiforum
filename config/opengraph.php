<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open Graph images
    |--------------------------------------------------------------------------
    |
    | Topics get a generated 1200×630 preview image for WhatsApp, Facebook and
    | the like. When the opening post has an image, it's used as a blurred,
    | darkened background. Otherwise `default_background` is used in the same
    | way, or a plain red background when it's empty.
    |
    | `default_background` is a path relative to the project root, for
    | example 'resources/img/og/background.jpg'.
    |
    */

    'default_background' => env('OPENGRAPH_DEFAULT_BACKGROUND'),

];
