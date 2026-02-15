<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case NON_BINARY = 'non_binary';

    public function label(): string
    {
        return __('enums/gender.' . $this->value);
    }

    public static function options(): Collection
    {
        return collect(static::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        });
    }
}
