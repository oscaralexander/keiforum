<?php

namespace App\Enums;

enum AdType: string
{
    case OFFERED = 'offered';
    case WANTED = 'wanted';

    public function label(): string
    {
        return __('enums/ad_type.' . $this->value);
    }
}