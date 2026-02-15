<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('livewire:clean')->hourly();
