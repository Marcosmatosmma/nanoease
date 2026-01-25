<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use App\Domain\Contracts\Jobs\CheckContractExpirationsJob;

Schedule::daily()
    ->onOneServer()
    ->group(fn () => [
        Schedule::command('sitemap:generate'),
        Schedule::job(new CheckContractExpirationsJob())->name('check-contract-expirations'),
    ]);

Schedule::command('automations:run-email --minutes-ago=5')
    ->everyFiveMinutes()
    ->name('email-automations')
    ->withoutOverlapping()
    ->runInBackground();
