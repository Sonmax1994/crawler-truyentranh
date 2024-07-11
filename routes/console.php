<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('insert:list-category')
	->dailyAt('01:00')
	->withoutOverlapping()
    ->onOneServer();

Schedule::command('upsert:detail-comic')
	->everyFiveMinutes()
	->withoutOverlapping()
    ->onOneServer();

Schedule::command('statistical:view-comics')
	->twiceDaily(10, 22)
	->withoutOverlapping()
    ->onOneServer();

Schedule::command('statistical:rank-comic')
	->dailyAt('23:20')
	->withoutOverlapping()
    ->onOneServer();