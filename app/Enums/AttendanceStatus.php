<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case GOING = 'going';
    case MAYBE = 'maybe';

    public function label(): string
    {
        return __('enums/attendance_status.'.$this->value);
    }
}
