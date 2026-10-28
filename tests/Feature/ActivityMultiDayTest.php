<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityMultiDayTest extends TestCase
{
    use RefreshDatabase;

    private function activity(string $endsOn, ?string $endsAt): Activity
    {
        return Activity::factory()->make([
            'starts_on' => '2099-10-24',
            'ends_on' => $endsOn,
            'starts_at' => '22:00',
            'ends_at' => $endsAt,
        ]);
    }

    public function test_same_day_activity_is_not_multi_day(): void
    {
        $this->assertFalse($this->activity('2099-10-24', '23:30')->is_multi_day);
    }

    public function test_activity_ending_the_next_night_is_not_multi_day(): void
    {
        $this->assertFalse($this->activity('2099-10-25', '05:59')->is_multi_day);
    }

    public function test_activity_ending_the_next_morning_is_multi_day(): void
    {
        $this->assertTrue($this->activity('2099-10-25', '06:00')->is_multi_day);
    }

    public function test_activity_ending_the_next_day_without_an_end_time_is_multi_day(): void
    {
        $this->assertTrue($this->activity('2099-10-25', null)->is_multi_day);
    }

    public function test_activity_ending_two_days_later_is_multi_day(): void
    {
        $this->assertTrue($this->activity('2099-10-26', '04:00')->is_multi_day);
    }
}
