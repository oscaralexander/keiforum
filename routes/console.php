<?php

use App\Jobs\FetchHeadlines;
use Illuminate\Support\Facades\Schedule;

Schedule::command('livewire:clean')->hourly();
Schedule::job(new FetchHeadlines)->everyFifteenMinutes();
