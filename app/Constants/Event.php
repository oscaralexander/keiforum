<?php

namespace App\Constants;

final readonly class Event
{
    /** Conversation events */
    public const CONVERSATION_OPENED = 'conversation-opened';

    public const CONVERSATION_CLOSED = 'conversation-closed';

    /** Topic events */
    public const TOPIC_UPDATED = 'topic-updated';

    public const REPLY_TO_POST = 'reply-to-post';
}
