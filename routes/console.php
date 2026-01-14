<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::daily()
    ->onOneServer()
    ->group(fn () => [
        Schedule::command('sitemap:generate'),
    ]);

Schedule::command('automations:run-email --minutes-ago=5')
    ->everyFiveMinutes()
    ->name('email-automations')
    ->withoutOverlapping()
    ->runInBackground();
