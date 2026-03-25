<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum ReportType: string
{
    case CONTENT = 'content';
    case BEHAVIOR = 'behavior';
    case VIOLATION = 'violation';
    case OTHER = 'other';

    public function description(): string
    {
        return __('enums/report_type.'.$this->value.'_description');
    }

    public function label(): string
    {
        return __('enums/report_type.'.$this->value);
    }

    public static function options(): Collection
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        });
    }
}
