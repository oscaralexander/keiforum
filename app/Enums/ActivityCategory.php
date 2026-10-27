<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum ActivityCategory: string
{
    case CLUB = 'club';
    case DANCE = 'dans';
    case ART = 'kunst';
    case MUSIC = 'muziek';
    case THEATER = 'theater';
    case OTHER = 'anders';

    public function label(): string
    {
        return __('enums/activity_category.'.$this->value);
    }

    public static function options(): Collection
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        });
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
