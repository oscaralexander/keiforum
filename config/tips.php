<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tips
    |--------------------------------------------------------------------------
    |
    | Tips shown in the sidebar, each linking to a topic. Their texts live in
    | lang/nl/tips.php. A tip is hidden when its topic no longer exists.
    |
    */

    'introduce' => [
        'topic_id' => (int) env('TIP_INTRODUCE_TOPIC_ID', 10),
    ],

];
