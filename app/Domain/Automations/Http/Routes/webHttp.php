<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domain\Automations\Http\Controllers\HttpRequestController;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('api/automations/http')
    ->name('api.automations.http.')
    ->group(function () {
        Route::post('/test', [HttpRequestController::class, 'test'])->name('test');
    });
