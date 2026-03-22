<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('livewire:clean')->hourly();
Schedule::job(new \App\Jobs\FetchHeadlines)->hourly();
