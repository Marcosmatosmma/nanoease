<?php

declare(strict_types=1);

use App\Domain\Integrations\Controllers\IntegrationController;
use App\Domain\Integrations\Controllers\GmailIntegrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/integrations', [IntegrationController::class, 'index'])
            ->name('integrations.index');

        Route::get('/integrations/gmail/redirect', [GmailIntegrationController::class, 'redirect'])
            ->name('integrations.gmail.redirect');

        Route::delete('/integrations/gmail/disconnect', [GmailIntegrationController::class, 'disconnect'])
            ->name('integrations.gmail.disconnect');
    });

Route::get('/integrations/gmail/callback', [GmailIntegrationController::class, 'callback'])
    ->name('integrations.gmail.callback');
