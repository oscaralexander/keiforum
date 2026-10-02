<?php

use Carbon\CarbonInterface;

if (! function_exists('time_diff')) {
    function time_diff(CarbonInterface $date, ?CarbonInterface $other = null, bool $long = false): string
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
                return ($value ?: 1).__('time_diff.'.$unit.($long ? '_long' : ''));
            }
        }

        return __('time_diff.now');
    }
}

if (! function_exists('safe_redirect_path')) {
    /**
     * Return the path when it is a local path that is safe to redirect to.
     */
    function safe_redirect_path(?string $path): ?string
    {
        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return null;
        }

        return $path;
    }
}

if (! function_exists('remember_redirect_path')) {
    /**
     * Store a safe `redirect` query parameter as the URL to return to after logging in.
     */
    function remember_redirect_path(): void
    {
        if ($path = safe_redirect_path(request()->query('redirect'))) {
            session()->put('url.intended', url($path));
        }
    }
}

if (! function_exists('login_url')) {
    /**
     * The login URL, returning to the current page after logging in.
     */
    function login_url(): string
    {
        $path = '/'.ltrim(request()->path(), '/');

        if ($path === '/' || request()->routeIs('login', 'register', 'register-oauth', 'forgot-password', 'reset-password', 'activate-account')) {
            return route('login');
        }

        return route('login', ['redirect' => $path]);
    }
}

if (! function_exists('versioned_asset')) {
    /**
     * The asset URL with a hash of the file's contents appended, so caches
     * fetch the file again whenever it changes.
     */
    function versioned_asset(string $path): string
    {
        static $hashes = [];

        $hashes[$path] ??= is_file(public_path($path))
            ? substr(md5_file(public_path($path)), 0, 8)
            : null;

        return $hashes[$path] ? asset($path).'?v='.$hashes[$path] : asset($path);
    }
}
