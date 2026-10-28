<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

class ActivityPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_whole_euros_are_shown_without_decimals(): void
    {
        $activity = Activity::factory()->make(['price' => 1000]);

        $this->assertSame(Number::currency(10, in: 'EUR', locale: 'nl', precision: 0), $activity->price_formatted);
        $this->assertStringNotContainsString(',', $activity->price_formatted);
    }

    public function test_cents_are_shown_with_two_decimals(): void
    {
        $activity = Activity::factory()->make(['price' => 1250]);

        $this->assertSame(Number::currency(12.5, in: 'EUR', locale: 'nl'), $activity->price_formatted);
        $this->assertStringContainsString('12,50', $activity->price_formatted);
    }

    public function test_free_activity_is_shown_as_free(): void
    {
        $activity = Activity::factory()->make(['price' => 0]);

        $this->assertSame(__('agenda/show.free'), $activity->price_formatted);
    }
}
