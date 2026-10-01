<?php

namespace App\Enums;

enum HeadlineVerdict: string
{
    case APPROVED = 'approved';
    case NEUTRAL = 'neutral';
    case BLOCKED = 'blocked';
}
