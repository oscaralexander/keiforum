<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum AdType: string
{
    case OFFERED = 'offered';
    case WANTED = 'wanted';

    public function label(): string
    {
        return __('enums/ad_type.' . $this->value);
    }

    public static function options(): Collection
    {
        return collect(static::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        });
    }
}