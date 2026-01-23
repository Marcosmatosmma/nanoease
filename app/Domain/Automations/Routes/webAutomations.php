<?php

declare(strict_types=1);

use App\Domain\Automations\Controllers\AutomationController;
use App\Domain\Automations\Controllers\AutomationTriggerTypeController;
use App\Domain\Automations\Controllers\ClassifiedEmailController;
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

        Route::get('/automations/email-mass-send', [AutomationController::class, 'emailMassSend'])
            ->name('automations.email-mass-send');

        Route::get('/automations/email-mass-send/{massEmailSend}', [AutomationController::class, 'emailMassSendView'])
            ->name('automations.email-mass-send.edit');

        Route::post('/automations/email-mass-send/{massEmailSend?}', [AutomationController::class, 'storeEmailMassSend'])
            ->name('automations.email-mass-send.store');

        Route::delete('/automations/email-mass-send/{massEmailSend}', [AutomationController::class, 'destroyMassEmailSend'])
            ->name('automations.mass-email-send.destroy');

        Route::post('/automations/email-mass-send/{massEmailSend}/resend', [AutomationController::class, 'resendMassEmailSend'])
            ->name('automations.mass-email-send.resend');

        Route::patch('/automations/{automation}/status', [AutomationController::class, 'updateStatus'])
            ->name('automations.status');

        Route::delete('/automations/{automation}', [AutomationController::class, 'destroy'])
            ->name('automations.destroy');

        Route::get('/emails/organized', [ClassifiedEmailController::class, 'index'])
            ->name('emails.organized');

        Route::get('/emails/organized/export', [ClassifiedEmailController::class, 'export'])
            ->name('emails.organized.export');

        Route::get('/gmail/labels', [ClassifiedEmailController::class, 'listGmailLabels'])
            ->name('gmail.labels');
    });
