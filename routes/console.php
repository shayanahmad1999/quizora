<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('quizora:expire')->everyMinute()->withoutOverlapping();
