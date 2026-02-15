<?php

use Carbon\CarbonInterface;

if (! function_exists('time_diff')) {
    function time_diff(CarbonInterface $date, ?CarbonInterface $other = null): string
    {
        $other ??= now();

        $units = [
            ['value' => (int) $date->diffInYears($other, false), 'unit' => 'years'],
            ['value' => (int) $date->diffInMonths($other, false), 'unit' => 'months'],
            ['value' => (int) $date->diffInWeeks($other, false), 'unit' => 'weeks'],
            ['value' => (int) $date->diffInDays($other, false), 'unit' => 'days'],
            ['value' => (int) $date->diffInHours($other, false), 'unit' => 'hours'],
            ['value' => (int) $date->diffInMinutes($other, false), 'unit' => 'minutes'],
            ['value' => (int) $date->diffInSeconds($other, false), 'unit' => 'seconds'],
        ];

        foreach ($units as ['value' => $value, 'unit' => $unit]) {
            $value = abs($value);

            if ($value >= 1 || $unit === 's') {
                return ($value ?: 1) . __('time_diff.' . $unit);
            }
        }

        return __('time_diff.now');
    }
}
