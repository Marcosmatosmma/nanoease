<?php

declare(strict_types=1);

use App\Domain\Automations\Controllers\AutomationController;
use App\Domain\Automations\Controllers\AutomationTriggerTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/automations', [AutomationController::class, 'index'])
            ->name('automations.index');

        Route::get('/automations/new', [AutomationController::class, 'selectEvent'])
            ->name('automations.new');

        Route::get('/api/automation-trigger-types/{eventKey}', [AutomationTriggerTypeController::class, 'index'])
            ->name('api.automation-trigger-types.index');

        Route::get('/automations/email-received', [AutomationController::class, 'emailReceived'])
            ->name('automations.email-received');

        Route::get('/automations/email-received/{automation}', [AutomationController::class, 'emailReceivedEdit'])
            ->name('automations.email-received.edit');

        Route::post('/automations/email-received/simulate', [AutomationController::class, 'simulateEmailReceived'])
            ->name('automations.email-received.simulate');

        Route::post('/automations/email-received/{automation?}', [AutomationController::class, 'storeEmailReceived'])
            ->name('automations.email-received.store');

        Route::patch('/automations/{automation}/status', [AutomationController::class, 'updateStatus'])
            ->name('automations.status');

        Route::delete('/automations/{automation}', [AutomationController::class, 'destroy'])
            ->name('automations.destroy');
    });
