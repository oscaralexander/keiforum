<?php

use App\Jobs\FetchHeadlines;
use App\Jobs\SendWeeklyDigest;
use Illuminate\Support\Facades\Schedule;

Schedule::command('livewire:clean')->hourly();
Schedule::job(new FetchHeadlines)->everyFifteenMinutes();
Schedule::job(new SendWeeklyDigest)->weeklyOn(0, '10:00');
