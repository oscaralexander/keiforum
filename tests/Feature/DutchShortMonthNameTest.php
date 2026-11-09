<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DutchShortMonthNameTest extends TestCase
{
    public function test_dutch_short_month_names_have_no_trailing_dot(): void
    {
        App::setLocale('nl');

        $date = Carbon::create(2026, 11, 14);

        $this->assertSame('14 nov', $date->translatedFormat('j M'));
        $this->assertSame('nov', $date->isoFormat('MMM'));
    }

    public function test_full_dutch_month_names_are_unchanged(): void
    {
        App::setLocale('nl');

        $this->assertSame('14 november 2026', Carbon::create(2026, 11, 14)->translatedFormat('j F Y'));
    }
}
